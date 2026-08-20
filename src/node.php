<?php

namespace cwmoss\lolql;

use PhpToken;

class node {

    public ?array $n = null;

    public function __construct(public $op = null, public $left = null, public $right = null) {
    }

    public function add(PhpToken|node|literal|path|parameter $token) {
        if (!$this->left) return $this->left = $token;
        if (!$this->op) return $this->op = $token;
        if (!$this->right) return $this->right = $token;
    }

    public function operator() {
        if (is_string($this->op)) return $this->op;
        return $this->op->text;
    }
}
