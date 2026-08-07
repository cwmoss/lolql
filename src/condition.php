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

    public function eval(array $item): bool {
        $left = $this->left->get_value($item);
        $right = $this->right->get_value($item);

        return match ($this->operator) {
            operator::eq => $this->cmp_eq($left, $right),
            operator::neq => !$this->cmp_eq($left, $right),
            operator::gt => $this->cmp_gt($left, $right),
            operator::lt => $this->cmp_lt($left, $right),
            operator::matches => $this->cmp_matches($left, $right),
            operator::in => $this->cmp_in($left, $right),
            default => false
        };
    }

    public function cmp_eq(mixed $left, mixed $right): bool {
        if ($this->left->is_key) {
            if (is_array($left))
                return array_search($right[0], $left);
            return $left == $right[0];
        }
        if ($this->right->is_key) {
            if (is_array($right)) {
                return array_search($left[0], $right);
            }
            return $left[0] == $right;
        }
        return ($left[0] == $right[0]);
    }

    public function cmp_matches(mixed $left, mixed $right): bool {
        if (!$this->right->is_value) {
            return false;
        }
        $val = $this->right->content[0];
        // arrays as name not supported for now
        if ($val[0] == '*') {
            return str_ends_with($left, ltrim($val, '*'));
        }

        if ($val[-1] == '*') {
            return str_starts_with($left, rtrim($val, '*'));
        }

        return str_contains($left, $val);
    }

    /*
title in ["Aliens", "Interstellar", "Passengers"]
"yolo" in tags
*/
    public function cmp_in(mixed $left, mixed $right): bool {
        #dbg("cmp in l r", $l, $r);

        if ($this->left->is_key) {
            $haystack = $right; #$l['v'];
            $needle = $left; #$r['v'][0];
        } else {
            $haystack = $right;
            $needle = $left[0];
        }
        // print_r(["in", $this, $left, $right]);
        if (!is_array($haystack)) return false;
        return in_array($needle, $haystack);
    }

    function cmp_lt(mixed $left, mixed $right): bool {
        if ($this->left->is_key) {
            if (is_array($left))
                return false;
            return $left < $right[0];
        }
        if ($this->right->is_key) {
            if (is_array($right)) {
                return false;
            }
            return $left[0] < $right;
        }
        return ($left[0] < $right[0]);
    }

    function cmp_gt(mixed $left, mixed $right): bool {
        if ($this->left->is_key) {
            if (is_array($left))
                return false;
            return $left > $right[0];
        }
        if ($this->right->is_key) {
            if (is_array($right)) {
                return false;
            }
            return $left[0] > $right;
        }
        return ($left[0] > $right[0]);
    }

    public function add_left_right_content(string $lr, mixed $content) {
        if ($lr == "l") $this->left->content[] = $content;
        else $this->right->content[] = $content;
    }
    public function update_left_right_type_key(string $lr) {
        if ($lr == "l") $this->left->update_type_key();
        else $this->right->update_type_key();
    }
    public function update_left_right_type_value(string $lr) {
        if ($lr == "l" && !$this->left->type) $this->left->update_type_value();
        else $this->right->update_type_value();
    }
}
