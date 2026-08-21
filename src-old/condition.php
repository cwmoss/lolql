<?php

namespace cwmoss\lolql;

class condition {

    // parsing starts filling the left side
    private bool $current_is_left = true;

    public array $n;

    public function __construct(
        public ?condition_part $left = null,
        public ?condition_part $right = null,
        public ?operator $operator = null,
        public ?logic_operator $next = null
    ) {
        if (!$left) $this->left = new condition_part();
        if (!$right) $this->right = new condition_part();
    }

    public function eval(array $item, array $params = []): bool {
        $left = $this->left->get_value($item, $params);
        $right = $this->right->get_value($item, $params);

        return match ($this->operator) {
            operator::eq => $this->cmp_eq($left, $right),
            operator::neq => !$this->cmp_eq($left, $right),
            operator::matches => $this->cmp_matches($left, $right),
            operator::in => $this->cmp_in($left, $right),
            operator::isnull => $this->cmp_null($left),
            operator::notnull => !$this->cmp_null($left),
            default => $this->cmp_non_arrays($left, $right),
        };
    }

    public function cmp_eq(mixed $left, mixed $right): bool {
        if ($this->left->is_key) {
            if (is_array($left))
                return array_search($right, $left);
            return $left == $right;
        }
        if ($this->right->is_key) {
            if (is_array($right)) {
                return array_search($left, $right);
            }
            return $left == $right;
        }
        return ($left == $right);
    }

    public function cmp_null(mixed $left): bool {
        return is_null($left);
    }

    public function cmp_matches(mixed $left, mixed $right): bool {
        if (!$this->right->is_value) {
            return false;
        }
        $val = $this->right->content;
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
            $needle = $left;
        }
        // print_r(["in", $this, $left, $right]);
        if (!is_array($haystack)) return false;
        return in_array($needle, $haystack);
    }

    public function get_non_array_values_for_compare(mixed $left, mixed $right) {
        if ($this->left->is_key) {
            if (is_array($left))
                return false;
            return [$left, $right];
        }
        if ($this->right->is_key) {
            if (is_array($right)) {
                return false;
            }
            return [$left, $right];
        }
        return [$left, $right];
    }

    public function cmp_non_arrays(mixed $left, mixed $right): bool {
        $vals = $this->get_non_array_values_for_compare($left, $right);
        if (!$vals) return false;
        return match ($this->operator) {
            operator::lt => $vals[0] < $vals[1],
            operator::lte => $vals[0] <= $vals[1],
            operator::gt => $vals[0] > $vals[1],
            operator::gte => $vals[0] >= $vals[1],
            default => false
        };
    }

    public function set_operator(operator $op) {
        $this->operator = $op;
        $this->current_is_left = false;
    }
    public condition_part $current {
        get => $this->current_is_left ? $this->left : $this->right;
    }
}
