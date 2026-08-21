<?php

namespace cwmoss\lolql;

class printer {

    public function print(node|literal|path|parameter|null $node): string {
        if ($node === null) {
            return "";
        }

        $lines = [];
        $this->render($node, '', false, $lines, true);
        return implode("\n", $lines);
    }

    private function render(mixed $node, string $prefix, bool $isLast, array &$lines, bool $isRoot = false): void {
        $connector = $isRoot ? '❤️  ' : ($isLast ? "└── " : "├── ");

        if ($node instanceof node) {
            $label = $node->op ? ($node->op->name ?? $node->operator()) : 'expr';
            $lines[] = $prefix . $connector . $label;

            $children = [];
            if ($node->left !== null) $children[] = $node->left;
            if ($node->right !== null) $children[] = $node->right;

            foreach ($children as $idx => $child) {
                $childPrefix = $prefix . ($isRoot ? '' : ($isLast ? '    ' : '│   '));
                $this->render($child, $childPrefix, $idx === count($children) - 1, $lines);
            }
            return;
        }

        $label = $this->leaf_label($node);
        $lines[] = $prefix . $connector . $label;
    }

    private function leaf_label(mixed $node): string {
        if ($node instanceof literal) {
            return $this->format_literal($node);
        }

        if ($node instanceof path) {
            return join('.', $node->parts);
        }

        if ($node instanceof parameter) {
            return '$' . $node->name;
        }

        if (is_string($node)) {
            return $node;
        }

        if (is_numeric($node)) {
            return (string) $node;
        }

        if (is_bool($node)) {
            return $node ? 'true' : 'false';
        }

        if (is_null($node)) {
            return 'null';
        }

        if (is_array($node)) {
            return '[' . implode(', ', array_map(fn($item) => $this->leaf_label($item), $node)) . ']';
        }

        return (string) $node;
    }

    private function format_literal(literal $literal): string {
        if (is_array($literal->value)) {
            return '[' . implode(', ', array_map(fn($value) => $this->leaf_label($value), $literal->value)) . ']';
        }

        return $this->leaf_label($literal->value);
    }
}
