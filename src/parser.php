<?php

namespace cwmoss\lolql;

use Closure;
use LogicException;

class parser {

    public function parse(string $source, array $params = []): query {
        $source = self::replace_params($source, $params);
        $tokens = new tokenizer($source)->tokenize();
        $toplevel = array_reduce(array_chunk($tokens, 2), function ($res, $kv) {
            // first function must be a filter
            $name  = $kv[0][0]->text ?? "projection";
            if (!$res) {
                if (!is_array($kv[1])) throw new LogicException("Could not parse expression. missing filter.");
            }
            $res[$name] = $kv[1];
            return $res;
        }, []);
        // print_r($toplevel);
        $qk = array_key_first($toplevel);
        $q = self::array_map_recursive_for_tokenlist(fn($it) => self::combine_tokens($it), $toplevel[$qk]);

        // special syntax for _type queries
        //  ex. person(dept=="development")
        //      => _type=="person" && dept=="development"
        $type = null;
        if (!(in_array($qk, ['q', '*', '😂', '❤️']))) {
            $type = $qk;
            /*
            array_unshift(
                $q,
                new condition(
                    new condition_part("k", null, ['_type']),
                    new condition_part("v", $qk),
                    operator::eq,
                    logic_operator::and
                )
            );
            */
        }

        $order = null;
        if (isset($toplevel['order']) && isset($toplevel['order'][0])) {
            $order = new order($toplevel['order'][0]);
        }
        $limit = null;
        if (isset($toplevel['limit']) && isset($toplevel['limit'][0])) {
            $limit = new limit($toplevel['limit'][0]);
        }
        $projection = null;

        return new query($q, $order, $limit, $projection, $type, isset($parts['count']), isset($parts['preview']));
    }

    static public function combine_tokens(array $tokens): array {
        // print_r($tokens);
        $buffer = new condition();
        $res = [];
        // TODO: use token-ids
        foreach ($tokens as $t) {
            $item = $t->text;
            if ($item == '&&' || $item == '||') {
                $buffer->next = logic_operator::from($item);
                $res[] = $buffer;
                $buffer = new condition();
                continue;
            }
            $op = operator::parse($item);
            if ($op) {
                $buffer->set_operator($op);
                continue;
            }
            // left or right condition_part
            $current = $buffer->current;
            if ($item[0] == '"') {
                $current->set_literal($item, '"');
            } elseif ($item[0] == "'") {
                $current->set_literal($item, "'");
            } elseif ($item[0] == '$') {
                $current->set_parameter($item);
            } elseif ($item == "[") {
                // maybe start a literal array
                if (!$current->type) {
                    $current->literal_type = "array";
                }
            } elseif (is_numeric($item)) {
                if ($current->type != "k") {
                    $current->set_literal($item);
                    $current->literal_type = "number";
                } else {
                    $current->add_path($item);
                }
            } elseif (!in_array($item, ['[', ']', '.', ','])) {
                $current->add_path($item);
            }
        }
        if ($buffer && $buffer->operator) {
            $res[] = $buffer;
        }
        return $res;
    }

    static public function array_map_recursive_for_tokenlist(Closure $fn, array $arr): array {
        return array_map(function ($item) use ($fn) {
            return is_object($item[0]) ? $fn($item) : array_map($fn, $item);
        }, $arr);
    }

    static public function array_map_recursive(Closure $fn, array $arr): array {
        return array_map(function ($item) use ($fn) {
            return is_array($item) ? array_map($fn, $item) : $fn($item);
        }, $arr);
    }

    static public function words(string $string): array {
        return array_filter(explode(' ', $string), 'trim');
    }

    static public function replace_params(string $q, array $params = []): string {
        foreach ($params as $k => $v) {
            $q = \str_replace('$' . $k, '"' . $v . '"', $q);
        }
        return $q;
    }
}
