<?php

namespace cwmoss\lolql;

use Closure;

class sql_query {

    public Closure $fun;
    public string $fun_name;
    public string $sql;
    public ?string $count_sql = null;
    public bool $limited = false;

    public function __construct(
        query $q,
        array $params = [],
        public string $table_name = "docs",
        public string $json_column_name = "body"
    ) {
        $this->make_query($q, $params);
    }

    public function make_query(query $query, array $params = []): self {
        $sql = 'SELECT %s from %s WHERE %s(%s)';
        $this->fun = $query->eval_cond_as_sql_function($params);
        $this->fun_name = 'lolql_' . bin2hex(\random_bytes(8));
        // $db->createFunction($name, $fn, 1);
        $q = sprintf(
            $sql,
            $this->json_column_name,
            $this->table_name,
            $this->fun_name,
            $this->json_column_name
        );
        $order = $query->order?->build_order_sql($this->propname(...));
        if ($order) {
            $q .= ' ORDER BY ' . $order;
        }
        $limit = $query->limit?->sql();
        if ($limit) {
            $q .= ' ' . $limit;
            $this->limited = true;
        }
        $this->sql = $q;
        if ($query->count) {
            if ($limit) {
                $qc = str_replace("SELECT body", "SELECT count(*)", $q);
                $qc = preg_replace("/LIMIT .*$/", "", $qc);
                $this->count_sql = $qc;
            }
        }
        return $this;
    }

    public function with_limit(int $limit, int $offset): self {
        if ($this->limited) {
            $this->sql = preg_replace("/LIMIT .*$/", "LIMIT $limit OFFSET $offset", $this->sql);
        } else {
            $this->limited = true;
            $this->sql .= " LIMIT $limit OFFSET $offset";
        }
        return $this;
    }

    public function decode_result(array $res): array {
        $col = $this->json_column_name;
        return array_map(
            fn($r) => json_decode($r[$col], true),
            $res
        );
    }

    public function propname(string $n): string {
        $name = sprintf(
            "json_extract(%s, '\$.%s')",
            $this->json_column_name,
            $n
        );
        return $name;
    }
}
