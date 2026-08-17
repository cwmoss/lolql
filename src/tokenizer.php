<?php

namespace cwmoss\lolql;

use PhpToken;

class tokenizer {

    public function __construct(public string $source) {
    }

    public function tokenize(): array {
        $tokens = PhpToken::tokenize('<?php ' . $this->source . ' ?>');
        // return $tokens;
        // $tokens = array_filter($tokens, fn($it)=>)
        return self::parse_parentheses($tokens);
        // return $tokens;
    }

    static public function parse_parentheses(array &$tokens, int $level = 1): array {
        $res = [];
        $buff = [];
        while ($token = array_shift($tokens)) {
            if ($token->is([\T_COMMENT, \T_DOC_COMMENT, \T_OPEN_TAG, \T_CLOSE_TAG, \T_WHITESPACE])) continue;
            if ($token->text == '(') {
                if ($buff) $res[] = $buff;
                $buff = [];
                $res[] = self::parse_parentheses($tokens, $level + 1);
                continue;
            }
            if ($token->text == ')') {
                break;
            }
            $buff[] = $token;
        }
        if ($buff) $res[] = $buff;
        return $res;
    }
    static public function parse_parentheses_without_recursion(array $tokens): array {
        $current = [];
        $stack = [];

        $buff = [];
        $level = 1;
        foreach ($tokens as $token) {
            if ($token->is(T_OPEN_TAG)) continue;
            if ($token->is(T_CLOSE_TAG)) continue;
            if ($token->is(T_WHITESPACE)) continue;
            switch ($token->text) {
                case '(':
                    // $push($current, $buffer_start, $position);
                    if ($buff) $current[] = $buff;
                    $buff = [];
                    // push current scope to the stack an begin a new scope
                    array_push($stack, $current);
                    $current = [];
                    $level++;
                    break;

                case ')':
                    // $push($current, $buffer_start, $position);
                    if ($buff) $current[] = $buff;
                    $buff = [];
                    // save current scope
                    $t = $current;
                    // get the last scope from stack
                    $current = array_pop($stack);
                    // add just saved scope to current scope
                    $current[] = $t;
                    $level--;
                    break;

                default:
                    // remember the offset to do a string capture later
                    // could've also done $buffer .= $string[$position]
                    // but that would just be wasting resources…
                    // if ($buffer_start === null) {
                    //    $buffer_start = $position;
                    //}
                    $buff[] = $token;
            }
        }
        // catch any trailing text
        if ($buff) $current[] = $buff;
        return $current;
    }
}
