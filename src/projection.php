<?php

namespace cwmoss\lolql;

class projection {

    public function __construct(
        public array $fields = []
    ) {
    }

    public function evaluate(array $data = [], array $params = []): array {
        $res = [];
        foreach ($this->fields as $f) {
            $v = evaluator::evaluate($f, $data, $params);
            $res[$v[0]] = $v[1];
        }
        return $res;
    }

    public function add(node $node) {
        if (!$node->op && !$node->left) return;
        if (!$node->op) {
            $node->op = operator::set;
            $node->right = $node->left;
            $node->left = new literal($node->right->parts[0]);
        }
        $this->fields[] = $node;
    }
}
