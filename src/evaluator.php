<?php

namespace cwmoss\lolql;

use Closure;

class evaluator {


    static public function filter_dataset(query $query, array $data = [], array $params = []): array {
        $fun = self::make_function_from_query($query);
        return array_filter($data, fn($data) => $fun($data, $params));
    }

    static public function make_sqlite_custom_function(node $node, array $params = []): Closure {
        $fun = self::make_function($node);
        return static function ($json_col) use ($params, $fun) {
            $data = json_decode($json_col, true);
            #print_r($item);
            #return true;
            $ok = $fun($data, $params);
            return $ok;
        };
    }

    static public function make_function_from_query(query $query): Closure {
        return static function (array $data = [], array $params = []) use ($query) {
            if ($query->type && (($data["_type"] ?? null) != $query->type)) return false;
            return self::evaluate($query->ast, $data, $params);
        };
    }

    static public function make_function(node $node): Closure {
        return static fn(array $data = [], array $params = []) => self::evaluate($node, $data, $params);
    }

    static public function evaluate_side(node|literal|path|parameter|null $node, array $data = [], array $params = []): mixed {
        return match (true) {
            $node instanceof node => self::evaluate($node, $data, $params),
            $node instanceof literal => $node->value,
            $node instanceof path => self::resolve_value($node->parts, $data),
            $node instanceof parameter => $params[$node->name],
            is_null($node) => true
        };
    }

    static public function evaluate(node $node, array $data = [], array $params = []): bool {
        // all
        if (!$node->left && !$node->right) return true;

        // single expression parenthesis
        if (!$node->op) {
            return self::evaluate($node->left, $data);
        }
        $left = self::evaluate_side($node->left, $data, $params);

        /*if (is_array($exp->n)) {
            $lft = array_map(fn($n) => $this->evaluate($n, $data), $exp->n);
        }*/
        // early return
        if ($node->op == operator::and && !$left) return false;
        if ($node->op == operator::or && $left) return true;

        // unary ops
        if ($node->op == operator::not) return !$left;

        $right = self::evaluate_side($node->right, $data, $params);
        //var_dump(['op', $exp->op, $lft, $rgt]);
        $res = self::eval_op($node->op, $left, $right);
        // var_dump(['res', $exp->op, $res]);
        return $res;
    }

    static public function eval_op(operator $op, mixed $lft, mixed $rgt): bool {
        // $lft = $data->get($lft);

        return match ($op) {
            operator::eq => self::cmp_eq($lft, $rgt),
            operator::eqt => $lft === $rgt,
            operator::neq => !self::cmp_eq($lft, $rgt),
            operator::lt => $lft < $rgt,
            operator::gt => $lft > $rgt,
            operator::lte => $lft <= $rgt,
            operator::gte => $lft >= $rgt,
            operator::and => $lft && $rgt,
            operator::or => $lft || $rgt,
            operator::in => in_array($lft, $rgt ?? []),
            operator::concat => $lft . $rgt,
            operator::matches => self::cmp_matches($lft, $rgt),
            operator::isnull => is_null($lft),
            operator::notnull => !is_null($lft),
            // ":" => [$lft, $rgt],
            // operator::call => $this->eval_call($rgt, $lft),
            // "array" => $this->eval_array($lft),
            // "object" => $this->eval_object($lft),
            // "+" => $lft + $rgt,
            // "-" => $lft - $rgt,
            // "*" => $lft * $rgt,
            // "/" => $lft / $rgt,
            // "%" => $lft % $rgt,
            // "**" => $lft ** $rgt,
        };
    }

    static public function cmp_eq(mixed $left, mixed $right): bool {
        if (is_array($left))
            return array_search($right, $left);

        if (is_array($right))
            return array_search($left, $right);

        return ($left == $right);
    }

    static public function cmp_matches(mixed $left, mixed $right): bool {
        if (!$right || !is_string($right)) {
            return false;
        }
        $val = $right;
        // arrays as name not supported for now
        if ($val[0] == '*') {
            return str_ends_with($left, ltrim($val, '*'));
        }

        if ($val[-1] == '*') {
            return str_starts_with($left, rtrim($val, '*'));
        }

        return str_contains($left, $val);
    }

    public function eval_array($items, $data) {
        $res = array_map(fn($e) => $data->get($e), $items);
        // print_r($res);
        return $res;
    }

    public function eval_call($meth, $args, $data) {
        // print "calling $meth\n";
        $args = array_map(fn($e) => $data->get($e), $args);
        return $data->call($meth, $args);
    }

    public function eval_object($els, $data) {
        // var_dump($els);
        $o = [];
        foreach ($els as $l) {
            $o[$l[0]] = $l[1];
        }
        return $o;
    }

    static public function resolve_value(array $keys, mixed $data): mixed {
        if (!$data) return null;

        $current = array_shift($keys);

        // nested?
        if ($keys) {
            return self::resolve_value($keys, $data[$current] ?? null);
        }

        if (is_array($data) && !self::is_assoc($data)) {
            return array_column($data, $current);
        } else {
            return $data[$current] ?? null;
        }
    }

    static public function is_assoc(array $arr): bool {
        if ([] === $arr) {
            return false;
        }
        // return array_keys($arr) !== range(0, count($arr) - 1);
        return !array_is_list($arr);
    }
}
