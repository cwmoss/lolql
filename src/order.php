<?php

namespace cwmoss\lolql;

class order {

    public array $parts = [];
    public function __construct(
        public string $source
    ) {
        $this->parse($source);
    }

    public function parse(string $source) {
        $this->parts = parser::words($source);
    }

    public function build_order_fun(): array {
        $os = [];
        foreach ($this->parts as $k => $o) {
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
