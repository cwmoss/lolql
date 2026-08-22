<?php
/*
lolql - lovely query language

make queries easy & keep it simple
*/
// https://www.sanity.io/docs/content-lake/query-cheat-sheet

namespace cwmoss\lolql;

use PDO;

class lolql {

    public query $query;

    public function __construct(string $query) {
        $this->query = new parser($query)->parse_query();
    }

    public function run(array $data, array $params = []): array {
        return $this->query->query($data, $params);
    }

    // sql with custom defined filter function 
    public function make_sql_function(array $params = []): sql_query {
        return new sql_query()->make_query($this->query, $params);
    }

    // sql with json expression
    public function make_sql(array $params = []): sql_query {
        return new sql_query()->make_query_sql($this->query, $params);
    }

    public function run_pdo(PDO\Sqlite $db, array $params = []): array {
        $q = $this->make_sql($params);

        $res = self::run_sql($db, $q->sql);
        $res = $q->decode_result($res);

        $pageinfo = [];
        if ($this->query->count) {
            if ($q->limited) {
                $total = self::run_sql_column($db, $q->count_sql());
            } else {
                $total = count($res);
            }
            $pageinfo = ['total' => $total];
        }
        if ($this->query->projection) {
            $res = array_map(fn($data) => $this->query->projection->evaluate($data, $params), $res);
        }
        return [$res, $pageinfo];
    }

    public function run_pdo_fn(PDO\Sqlite $db, array $params = []): array {
        $q = $this->make_sql_function($params);

        $db->createFunction($q->fun_name, $q->fun, 1);

        $res = self::run_sql($db, $q->sql);
        $res = $q->decode_result($res);

        $pageinfo = [];
        if ($this->query->count) {
            if ($q->limited) {
                $total = self::run_sql_column($db, $q->count_sql());
            } else {
                $total = count($res);
            }
            $pageinfo = ['total' => $total];
        }
        if ($this->query->projection) {
            $res = array_map(fn($data) => $this->query->projection->evaluate($data, $params), $res);
        }
        return [$res, $pageinfo];
    }

    public function limit_one(): self {
        $this->query->limit_one();
        return $this;
    }
    static public function run_sql(PDO $pdo, string $q): array {
        $fetchStyle = PDO::FETCH_ASSOC;
        $stmt = $pdo->prepare($q);
        $stmt->execute();
        $results = $stmt->fetchAll($fetchStyle);
        return $results;
    }

    static public function run_sql_column(PDO $pdo, string $q): mixed {
        $stmt = $pdo->prepare($q);
        $stmt->execute();
        return $stmt->fetchColumn(0);
    }

    static public function basic_schema(): string {
        return <<<DDL
CREATE TABLE IF NOT EXISTS docs (
    body JSON,
    _id TEXT GENERATED ALWAYS AS (json_extract(body, '$._id'))
        VIRTUAL
        NOT NULL
        ,
    _type TEXT GENERATED ALWAYS AS (json_extract(body, '$._type'))
        VIRTUAL
        NOT NULL
)
DDL;
    }
}
