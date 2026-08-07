<?php

namespace cwmoss\lolql;


/*
    query represents a parsed lolql query string
*/

class condition {

    public function __construct(
        public ?condition_part $left = null,
        public ?condition_part $right = null,
        public ?operator $operator = null,
        public ?logic_operator $next = null
    ) {
        if (!$left) $this->left = new condition_part();
        if (!$right) $this->right = new condition_part();
    }

    public function add_left_right_content(string $lr, mixed $content) {
        if ($lr == "l") $this->left->content[] = $content;
        else $this->right->content[] = $content;
    }
    public function update_left_right_type(string $lr, string $type) {
        if ($lr == "l" && !$this->left->type) $this->left->type = $type;
        elseif (!$this->right->type) $this->right->type = $type;
    }
}
