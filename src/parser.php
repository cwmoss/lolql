<?php

namespace cwmoss\lolql;

use Closure;
use LogicException;

class parser {

    public function parse(string $source, array $params = []): query {
        $source = self::remove_comments_and_newlines($source);
        $source = self::replace_params($source, $params);

        if (!$source) {
            // no string, no data
            return new query();
        }
        $parts = self::parse_parentheses($source);
        $parts = array_reduce(array_chunk($parts, 2), function ($res, $kv) {
            $res[trim($kv[0])] = $kv[1];
            return $res;
        }, []);
        $qk = array_key_first($parts);
        if (!is_array($parts[$qk])) throw new LogicException("Could not parse expression $source");

        $q = self::array_map_recursive(fn($it) => self::parse_condition($it), $parts[$qk]);

        // special syntax for _type queries
        //  ex. person(dept=="development")
        //      => _type=="person" && dept=="development"
        if (!($qk == 'q' || $qk == '*' || $qk == '😂' || $qk == '❤️')) {
            array_unshift(
                $q,
                new condition(
                    new condition_part("k", null, ['_type']),
                    new condition_part("v", $qk),
                    operator::eq,
                    logic_operator::and
                )
            );
        }

        $order = null;
        if (isset($parts['order']) && isset($parts['order'][0])) {
            $order = new order($parts['order'][0]);
        }
        $limit = null;
        if (isset($parts['limit']) && isset($parts['limit'][0])) {
            $limit = new limit($parts['limit'][0]);
        }
        $projection = null;

        return new query($q, $order, $limit, $projection, isset($parts['count']), isset($parts['preview']));
    }

    static public function parse_condition(string $string): array {
        $t = token_get_all('<?php ' . $string . ' ?>');
        $t = self::compact_tokens($t);
        $t = self::combine_tokens($t);
        return $t;
    }

    static public function combine_tokens(array $tokens): array {
        // print_r($tokens);
        $buffer = new condition();
        $res = [];
        foreach ($tokens as $item) {
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
            } elseif ($item == "[") {
                // maybe start a literal array
                if (!$current->type) {
                    $current->literal_type = "array";
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

    static public function compact_tokens(array $t): array {
        $t = array_map(function ($tok) {
            if (is_array($tok)) {
                return $tok[0] == T_OPEN_TAG || $tok[0] == T_CLOSE_TAG ? '' : $tok[1];
            }
            return $tok;
        }, $t);
        $t = array_filter($t, 'trim');
        return $t;
    }

    static public function array_map_recursive(Closure $fn, array $arr): array {
        return array_map(function ($item) use ($fn) {
            return is_array($item) ? array_map($fn, $item) : $fn($item);
        }, $arr);
    }

    static public function words(string $string): array {
        return array_filter(explode(' ', $string), 'trim');
    }

    static public function remove_comments_and_newlines(string $string): string {
        return join(' ', array_filter(
            explode("\n", $string),
            fn($line) => trim($line)[0] != '#'
        ));
    }

    static public function replace_params(string $q, array $params = []): string {
        foreach ($params as $k => $v) {
            $q = \str_replace('$' . $k, '"' . $v . '"', $q);
        }
        return $q;
    }

    /**
     * Parse a string into an array.
     *
     */
    // https://stackoverflow.com/questions/196520/php-best-way-to-extract-text-within-parenthesis
    // https://stackoverflow.com/questions/2650414/php-curly-braces-into-array

    // @rodneyrehm
    // http://stackoverflow.com/a/7917979/99923

    static public function parse_parentheses(string $string): array {
        if ($string[0] == '(') {
            // killer outer parens, as they're unnecessary
            $string = substr($string, 1, -1);
        }

        $buffer_start = null;
        $position = null;
        $current = [];
        $stack = [];

        $push = function (&$current, $string, &$buffer_start, $position) {
            if ($buffer_start === null) {
                return;
            }
            $buffer = substr($string, $buffer_start, $position - $buffer_start);
            $buffer_start = null;
            $current[] = $buffer;
        };

        for ($position = 0; $position < strlen($string); $position++) {
            switch ($string[$position]) {
                case '(':
                    $push($current, $string, $buffer_start, $position);
                    // push current scope to the stack an begin a new scope
                    array_push($stack, $current);
                    $current = [];
                    break;

                case ')':
                    $push($current, $string, $buffer_start, $position);
                    // save current scope
                    $t = $current;
                    // get the last scope from stack
                    $current = array_pop($stack);
                    // add just saved scope to current scope
                    $current[] = $t;
                    break;
                /*
                 case ' ':
                     // make each word its own token
                     $this->push();
                     break;
                 */
                default:
                    // remember the offset to do a string capture later
                    // could've also done $buffer .= $string[$position]
                    // but that would just be wasting resources…
                    if ($buffer_start === null) {
                        $buffer_start = $position;
                    }
            }
        }
        // catch any trailing text
        if ($buffer_start < $position) {
            $push($current, $string, $buffer_start, $position);
        }
        return $current;
    }
}
