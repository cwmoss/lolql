<?php

namespace cwmoss\lolql;

use Closure;

class order {

    public array $orders = [];
    // ../slowfoot/src/store/sqlite.php
    public array $raw = [];
    public Closure $fun;

    public function __construct(
        string|array $source
    ) {
        if (is_array($source)) {
            $this->combine_tokens($source);
        } else {
            $this->orders = array_map(parser::words(...), explode(",", $source));
        }
        $this->parse();
    }

    public function combine_tokens(array $tokens) {
        $buff = [];
        foreach ($tokens as $t) {
            if ($t->text == ",") {
                $this->orders[] = $buff;
                $buff = [];
            } else {
                $buff[] = $t->text;
            }
        }
        if ($buff) $this->orders[] = $buff;
    }
    public function parse() {
        [$fun, $raw] = $this->build_order_fun();
        $this->fun = $fun;
        $this->raw = $raw;
    }

    public function build_order_sql(?Closure $prop_name_fn = null): string {
        if (!$this->raw) return "";
        if (!$prop_name_fn) $prop_name_fn = fn($n) => $n;

        $sql = [];
        foreach ($this->raw as $order) {
            $sql[] = $prop_name_fn($order['k']) . ' ' . $order['d'];
        }
        return join(", ", $sql);
    }

    public function build_order_fun(): array {
        $os = [];
        foreach ($this->orders as $k => $o) {
            //$key = $dir = $cmp = null;
            // keys must start with 0, 1, 2...
            list($key, $dir, $cmp) = array_merge($o) + ['', '', ''];
            //print "key, $key";
            if ($dir && ($dir != 'asc' && $dir != 'desc')) {
                $cmp = $dir;
                $dir = 'asc';
            } elseif (!$dir) {
                $dir = 'asc';
            }
            $os[] = [
                'k' => $key,
                'd' => $dir,
                'c' => $cmp
            ];
        }
        $coll = collator_create('de_DE');
        return [
            function ($a, $b) use ($os, $coll) {
                foreach ($os as $order) {
                    //$cmp = 'strnatcasecmp';
                    $cmp = 'collator_compare';
                    $r = $cmp($coll, $a[$order['k']], $b[$order['k']]);
                    if ($r) {
                        return $order['d'] == 'desc' ? (-1 * $r) : $r;
                    }
                }
                return 0;
                //return strnatcmp($a[$key], $b[$key]);
            },
            $os
        ];
    }
}
