<?php

namespace cwmoss\lolql;

use Closure;

class sql_query {

    public Closure $fun;
    public string $fun_name;
    public string $sql;
    public bool $limited = false;

    public function __construct(
        public string $table_name = "docs",
        public string $json_column_name = "body"
    ) {
    }

    public function count_sql(): string {
        $qc = str_replace("SELECT body", "SELECT count(*)", $this->sql);
        if ($this->limited) {
            return preg_replace("/LIMIT .*$/", "", $qc);
        }
        return $qc;
    }

    public function make_query(query $query, array $params = []): self {
        $sql = 'SELECT %s from %s WHERE %s %s(%s)';
        // $this->fun = $query->eval_cond_as_sql_function($params);
        $this->fun = evaluator::make_sqlite_custom_function($query->ast, $params);
        $this->fun_name = 'lolql_' . bin2hex(\random_bytes(8));
        $type = "";
        if ($query->type) $type = "_type=='{$query->type}' AND ";
        // $db->createFunction($name, $fn, 1);
        $q = sprintf(
            $sql,
            $this->json_column_name,
            $this->table_name,
            $type,
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
        return $this;
    }

    public function make_query_sql(query $query, array $params = []): self {
        $conditions = [];

        if ($query->type) {
            $conditions[] = sprintf(
                "json_extract(%s, '$._type') = %s",
                $this->json_column_name,
                $this->literal_sql($query->type)
            );
        }

        $ast_sql = $this->node_to_sql($query->ast, $params);
        if ($ast_sql && $ast_sql !== '1=1') {
            $conditions[] = $ast_sql;
        }

        $where = $conditions ? ' WHERE ' . implode(' AND ', $conditions) : '';
        $q = sprintf('SELECT %s FROM %s%s', $this->json_column_name, $this->table_name, $where);

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
        return $this;
    }

    private function node_to_sql(node|literal|path|parameter|null $node, array $params = []): string {
        if ($node === null) {
            return '1=1';
        }

        if ($node instanceof node) {
            if (!$node->left && !$node->right) {
                return '1=1';
            }

            if (!$node->op) {
                return $this->node_to_sql($node->left, $params);
            }

            if ($node->op === operator::and) {
                $lhs = $this->node_to_sql($node->left, $params);
                $rhs = $this->node_to_sql($node->right, $params);
                return "({$lhs} AND {$rhs})";
            }

            if ($node->op === operator::or) {
                $lhs = $this->node_to_sql($node->left, $params);
                $rhs = $this->node_to_sql($node->right, $params);
                return "({$lhs} OR {$rhs})";
            }

            if ($node->op === operator::not) {
                return '(NOT ' . $this->node_to_sql($node->left, $params) . ')';
            }

            $lhs = $this->node_to_sql($node->left, $params);
            $rhs = $this->node_to_sql($node->right, $params);

            return match ($node->op) {
                operator::eq => $this->comparison_sql($lhs, $rhs, '=', $node->left, $node->right),
                operator::neq => $this->comparison_sql($lhs, $rhs, '!=', $node->left, $node->right),
                operator::lt => "({$lhs} < {$rhs})",
                operator::gt => "({$lhs} > {$rhs})",
                operator::lte => "({$lhs} <= {$rhs})",
                operator::gte => "({$lhs} >= {$rhs})",
                operator::in => $this->in_sql($lhs, $rhs, $node->left, $node->right),
                operator::matches => $this->matches_sql($lhs, $rhs),
                operator::isnull => "({$lhs} IS NULL)",
                operator::notnull => "({$lhs} IS NOT NULL)",
                default => "({$lhs} = {$rhs})",
            };
        }

        if ($node instanceof path) {
            return $this->propname(join('.', $node->parts));
        }

        if ($node instanceof parameter) {
            $value = $params[$node->name] ?? null;
            return $this->literal_sql($value);
        }

        if ($node instanceof literal) {
            if (is_array($node->value)) {
                return '(' . implode(', ', array_map(fn($value) => $this->literal_sql($value), $node->value)) . ')';
            }
            return $this->literal_sql($node->value);
        }

        return '1=1';
    }

    private function comparison_sql(string $left, string $right, string $operator, mixed $leftNode, mixed $rightNode): string {
        if (($leftNode instanceof path || $rightNode instanceof path) && !($leftNode instanceof literal && is_array($leftNode->value)) && !($rightNode instanceof literal && is_array($rightNode->value))) {
            $valueExpr = $leftNode instanceof path ? $right : $left;
            $jsonExpr = $leftNode instanceof path ? $left : $right;

            $arrayClause = "(json_type({$jsonExpr}) = 'array' AND EXISTS (SELECT 1 FROM json_each({$jsonExpr}) WHERE value {$operator} {$valueExpr}))";
            $scalarClause = "({$left} {$operator} {$right})";
            return "({$arrayClause} OR {$scalarClause})";
        }

        return "({$left} {$operator} {$right})";
    }

    private function in_sql(string $left, string $right, mixed $leftNode, mixed $rightNode): string {
        if ($rightNode instanceof literal && is_array($rightNode->value)) {
            return "({$left} IN {$right})";
        }

        if ($rightNode instanceof path) {
            return "EXISTS (SELECT 1 FROM json_each({$right}) WHERE value = {$left})";
        }

        if ($leftNode instanceof literal && is_array($leftNode->value)) {
            return "({$left} IN {$right})";
        }

        if ($leftNode instanceof path) {
            return "EXISTS (SELECT 1 FROM json_each({$left}) WHERE value = {$right})";
        }

        return "({$left} IN {$right})";
    }

    private function matches_sql(string $left, string $right): string {
        $pattern = trim($right, "'\"");
        $like = $pattern;
        if (str_starts_with($pattern, '*')) {
            $like = '%' . ltrim($pattern, '*');
        }
        if (str_ends_with($pattern, '*')) {
            $like = rtrim($pattern, '*') . '%';
        }
        if (!str_contains($pattern, '*')) {
            $like = '%' . $pattern . '%';
        }
        return "({$left} LIKE '{$this->escape_sql_string($like)}')";
    }

    private function literal_sql(mixed $value): string {
        if (is_null($value)) {
            return 'NULL';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        if (is_array($value)) {
            return '(' . implode(', ', array_map(fn($item) => $this->literal_sql($item), $value)) . ')';
        }

        return "'" . $this->escape_sql_string((string) $value) . "'";
    }

    private function escape_sql_string(string $value): string {
        return str_replace("'", "''", $value);
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
