<?php
require_once 'helper.php';

if (!function_exists('str_ends_with')) {
    function str_ends_with($haystack, $needle) {
        $len = strlen($needle);
        if ($haystack < $len) {
            return false;
        }
        $offset = -1 * $len;
        return strpos($haystack, $needle, $offset) !== false;
    }
}

if (!function_exists('str_starts_with')) {
    function str_starts_with($haystack, $needle) {
        return strpos($haystack, $needle) === 0;
    }
}

if (!function_exists('str_contains')) {
    function str_contains($haystack, $needle) {
        return strpos($haystack, $needle) !== false;
    }
}

$db = [
    ['_id' => 'a1', 'title' => 'hey', 'status' => 'draft', 'authors' => [['_ref' => '2'], ['_ref' => '4']]],
    ['_id' => 'a2', 'title' => 'hello', 'status' => 'published'],
    ['_id' => 'a3', 'title' => 'world', 'status' => 'published'],
    ['_id' => 'a4', 'title' => 'hello world', 'status' => 'published'],
    ['_id' => 'a5', 'title' => 'world is caos', 'status' => 'published'],
    ['_id' => 'a5', 'title' => 'yourworld is caos', 'status' => 'published'],
];

$t = [
    [
        [
            'l' => ['t' => 'k', 'c' => ['title']],
            'r' => ['t' => 'v', 'c' => ['hello']],
            'o' => '==',
            'x' => '&&'
        ],
        [
            'l' => ['t' => 'k', 'c' => ['status']],
            'r' => ['t' => 'v', 'c' => ['published']],
            'o' => '==',
            'x' => ''
        ],
    ],
    [
        [
            'l' => ['t' => 'k', 'c' => ['title']],
            'r' => ['t' => 'v', 'c' => ['hey']],
            'o' => '==',
            'x' => '&&'
        ],
        [
            'l' => ['t' => 'k', 'c' => ['status']],
            'r' => ['t' => 'v', 'c' => ['published']],
            'o' => '==',
            'x' => ''
        ],
    ],
    [
        [
            'l' => ['t' => 'k', 'c' => ['title']],
            'r' => ['t' => 'v', 'c' => ['world']],
            'o' => '==',
            'x' => '||'
        ],
        [
            'l' => ['t' => 'k', 'c' => ['status']],
            'r' => ['t' => 'v', 'c' => ['draft']],
            'o' => '==',
            'x' => ''
        ],
    ],
    [
        [
            'l' => ['t' => 'k', 'c' => ['authors', '_ref']],
            'r' => ['t' => 'v', 'c' => ['4']],
            'o' => '==',
            'x' => ''
        ],
    ],
    [
        [
            'l' => ['t' => 'k', 'c' => ['authors', '_ref']],
            'r' => ['t' => 'v', 'c' => ['8']],
            'o' => '==',
            'x' => ''
        ],
    ],
    [
        [
            'l' => ['t' => 'k', 'c' => ['title']],
            'r' => ['t' => 'v', 'c' => ['world']],
            'o' => 'matches',
            'x' => ''
        ]
    ],
    [
        [
            'l' => ['t' => 'k', 'c' => ['title']],
            'r' => ['t' => 'v', 'c' => ['*world']],
            'o' => 'matches',
            'x' => ''
        ]
    ],
    [
        [
            'l' => ['t' => 'k', 'c' => ['title']],
            'r' => ['t' => 'v', 'c' => ['world*']],
            'o' => 'matches',
            'x' => ''
        ]
    ],
];

foreach ($t as $test) {
    print_r($test);
    print_r(eval_cond($db, $test));
}

function eval_cond($db, $query) {
    return array_filter($db, function ($item) use ($query) {
        foreach ($query as $q) {
            $ok = evaluate_single($q['l'], $q['r'], $q['o'], $item);
            dbg('eval result', $ok);
            if (!$ok && $q['x'] == '&&') {
                return false;
            }
            if ($ok && $q['x'] == '||') {
                return true;
            }
        }
        return $ok;
    });
}

function evaluate($cond, $data) {
    foreach ($cond as $k => $v) {
        $ok = evaluate_single($k, $v, $data);
        if (!$ok) {
            return false;
        }
    }
    return true;
}
function evaluate_single($l, $r, $op, $data) {
    if ($l['t'] == 'k') {
        $l['v'] = get_value($l['c'], $data);
    } else {
        $l['v'] = get_literal($l['c']);
    }
    if ($r['t'] == 'k') {
        $r['v'] = get_value($r['c'], $data);
    } else {
        $r['v'] = get_literal($r['c']);
    }

    if ($op == '==') {
        $cmp = 'cmp_eq';
    } elseif ($op == 'matches') {
        $cmp = 'cmp_matches';
    } else {
        return false;
    }

    return $cmp($l, $r);
}

/*
title == "hello"
title == subtitle
authors._ref == "a19"
*/
function cmp_eq($l, $r) {
    dbg('cmp +++ ', $l, $r);

    if ($l['t'] == 'k' && is_array($l['v'])) {
        return array_search($r['v'][0], $l['v']);
    }
    if ($l['t'] == 'k') {
        return ($l['v'] == $r['v'][0]);
    }
    if ($r['t'] == 'k' && is_array($r['v'])) {
        return array_search($l['v'][0], $r['v']);
    }
    if ($r['t'] == 'k') {
        return ($l['v'][0] == $r['v']);
    }
    return ($l['v'][0] == $r['v'][0]);
}

/*
title matches "world"
title matches "world*"
title matches "*world"
*/
function cmp_matches($l, $r) {
    if ($r['t'] != 'v') {
        return false;
    }
    $val = $r['c'][0];
    // arrays as name not supported for now
    if ($val[0] == '*') {
        return str_ends_with($l['v'], ltrim($val, '*'));
    }

    if ($val[-1] == '*') {
        return str_starts_with($l['v'], rtrim($val, '*'));
    }

    return str_contains($l['v'], $val);
}

/*
title in ["Aliens", "Interstellar", "Passengers"]
"yolo" in tags
*/
function cmp_in($l, $r) {
    if ($l['t'] == 'k') {
        $haystack = $l['v'];
        $needle = $r['v'][0];
    } else {
        $haystack = $r['v'];
        $needle = $r['v'][0];
    }
    return in_array($needle, $haystack);
}

function get_value($keys, $data) {
    $current = array_shift($keys);

    // nested?
    if ($keys) {
        return get_value($keys, $data[$current]);
    }

    if (!$data) {
        return null;
    }

    if (!is_assoc($data)) {
        return array_column($data, $current);
    } else {
        return $data[$current];
    }
}

function get_literal($data) {
    return $data;
}

function array_find($haystack, $needle, $prop) {
    foreach ($haystack as $val) {
        if ($val[$prop] == $needle) {
            return true;
        }
    }
    return false;
}

function is_assoc(array $arr) {
    if ([] === $arr) {
        return false;
    }
    return array_keys($arr) !== range(0, count($arr) - 1);
}
