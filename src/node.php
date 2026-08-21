<?php

namespace cwmoss\lolql;

use PhpToken;

class node {

    public function __construct(public ?operator $op = null, public $left = null, public $right = null, public int $level = 0) {
    }

    public function add(node|literal|path|parameter|operator $token) {
        if ($token instanceof operator) {
            $this->op = $token;
            return;
        }
        if (!$this->left) return $this->left = $token;
        // if (!$this->op) return $this->op = $token;
        if (!$this->right) return $this->right = $token;
    }

    public function operator() {
        if (is_string($this->op)) return $this->op;
        return $this->op->text;
    }

    // public condition_part $current {
    //    get => $this->current_is_left ? $this->left : $this->right;
    //}
}
