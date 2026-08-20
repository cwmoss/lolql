<?php

namespace cwmoss\lolql;

use Exception;
use PhpToken;

class syntax_exception extends Exception {
    private $token;
    private $src;

    public function __construct(string $message, PhpToken $token, string $src) {
        $this->token = $token;
        $this->src = $src;
        parent::__construct($this->make_message($message, $token, $src));
    }

    public function make_message(string $message, PhpToken $token, string $src) {
        $msg = "lolql syntax error\n" . $src . "\n" . str_repeat(' ', ($token->pos - 6)) . '^ ' . $message;
        return $msg;
    }
}
