<?php

namespace cwmoss\lolql;

use PhpToken;

class ast {

    public array $query;
    public node $root;

    public $prec = [
        '**' => 40,
        '%' => 30,
        '/' => 30,
        '*' => 30,
        '-' => 25,
        '+' => 25,
        '~' => 20,
        'in' => 10,
        '===' => 10,
        '==' => 10,
        '!=' => 10,
        '<' => 10,
        '<=' => 10,
        '>' => 10,
        '>=' => 10,
        '!' => 35,
        '&&' => 4,
        '||' => 3,
        ':' => 1,
        'call' => 41
    ];

    public function __construct(
        public string $source
    ) {
        $tokens = PhpToken::tokenize('<?php ' . $source . ' ?>');
        $this->query = $this->parse_query($tokens);
        //$this->root = $this->parse($tokens);
    }

    /** @param PhpToken[] $tokens */
    public function parse_query(array &$tokens, int $minprec = 0, int $level = 0): array {
        $query = [];
        $fn = null;
        while ($token = array_shift($tokens)) {
            if ($token->is([\T_COMMENT, \T_DOC_COMMENT, \T_OPEN_TAG, \T_CLOSE_TAG, \T_WHITESPACE])) continue;
            $peek = $tokens[0] ?? new PhpToken(999, "");
            if ($token->text == "(") {
                throw new syntax_exception("missing function name. got `{$token->text}` instead", $token, $this->source);
            }
            if ($peek->text != "(") {
                throw new syntax_exception("missing function input. got `{$peek->text}` instead", $peek, $this->source);
            }
            array_shift($tokens);
            print "START tlF {$tokens[0]}\n";
            $query[] = new node(operator::call, $token->text, $this->parse($tokens));
            print "END tlF {$tokens[0]}\n";
            // $end = array_shift($tokens);
            // if ($end->text != ")") {
            //    throw new syntax_exception("missing closing parenthesis. got {$end->text} instead", $end, $this->source);
            //}
        }
        return $query;
    }

    /** @param PhpToken[] $tokens */
    public function parse(array &$tokens, int $minprec = 0, int $level = 0): node {
        $node = new node;
        while ($token = array_shift($tokens)) {
            if ($token->is([\T_COMMENT, \T_DOC_COMMENT, \T_OPEN_TAG, \T_CLOSE_TAG, \T_WHITESPACE])) continue;
            $peek = $tokens[0] ?? new PhpToken(999, "");
            $txt = $token->text;
            if ($txt === ',') {
                # $this->stream->next();
                return $node;
            }
            if ($txt === ')') {
                #print_r($node);
                #print "return0 from )\n";
                print "closing ) $level\n";
                return $node;
            }
            if ($txt === '!') {
                // TODO: next must be (
                $op = operator::not;
                array_shift($tokens);
                $rval = $this->parse($tokens, $this->prec[$op->value], $level + 1);
                $n = new node($op, $rval);
                $node->add($n);
                // return $rval;
                continue;
            }
            // method?
            if ($peek->text === '(' && $txt !== '(') {
                #print "++call++";
                #print_r($node);
                #$node->op = "call";
                #$node->left = $token->text;
                // TODO: better all args as list of trees not deep-tree? 
                array_shift($tokens);
                $n = new node(operator::call, $token->text, $this->parse($tokens, 0, $level + 1));
                $node->add($n);
                // return $node;
                continue;
            }
            if ($txt === '(') {
                #print "start bracket $level\n";
                $node->add($this->parse($tokens, 0, $level + 1));
                continue;
            }
            if ($txt == '&&' || $txt == '||') {
                // print "and-or:";
                // print_r($node);
                $node = new node(operator::parse($txt), $node);
                $node->add($this->parse($tokens, $this->prec[$txt], $level + 1));
                return $node;
            }
            $op = operator::parse($txt);
            if ($op) {
                $node->op = $op;
                continue;
            }
            match ($token->id) {
                \T_CONSTANT_ENCAPSED_STRING => $node->add(new literal($txt, $txt[0])),
                \T_LNUMBER => $node->add(new literal($txt)),
                \T_VARIABLE => $node->add(new parameter($txt)),
                \T_STRING => $node->add($this->parse_path($tokens, $txt)),
                default => throw new syntax_exception("unkown token ({$txt})", $token, $this->source)
            };

            continue;

            print "ADD level $level - $token->text\n";
            print_r($node);
            $node->add($token);
            if (!$node->op) continue;

            $op_prec = $this->prec[$node->operator()] ?? null;
            if ($op_prec === null) {
                #helper::dbg('+ op failed', $node);
                print_r($node);
                throw new syntax_exception("unkown operator ({$node->operator()})", $token, $this->source);
            }
            if ($op_prec <= $minprec) {
                // break;
            }

            $node->add($this->parse($tokens, $op_prec, $level + 1));
        }
        return $node;
    }

    public function parse_path(array &$tokens, string $start): path {
        $path = [$start];
        while (true) {
            if ($tokens[0]?->text == ".") {
                array_shift($tokens);
                $part = array_shift($tokens);
                // TODO: see that part is valid path-part
                $path[] = $part->text;
            } else {
                break;
            }
        }
        return new path($path);
    }
}
