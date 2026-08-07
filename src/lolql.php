<?php
/*

lolql - lovely query language

make queries easy & keep it simple

*/

namespace lolql;

require_once __DIR__ . '/compare.php';

// TODO: slice/ limit
function query($ds, $query, $params = []) {
    $query = is_string($query) ? parse($query, $params) : $query;

    // TODO: params aus dem parsing herausnehmen und zum evaluierungszeitpunkt einfügen
    $rs = eval_cond($ds, $query['q']);

    if ($query['order']) {
        usort($rs, $query['order']);
    }

    return $rs;
}


function eval_cond($db, $query) {
    $evaluator = get_evaluator($query);

    return array_filter($db, function ($item) use ($query, $evaluator) {
        // dbg('item-compare...', $item['_id'], $item['title']);
        [$ok, $next] = $evaluator($query, $item);
        return $ok;
    });
}

function eval_cond_as_sql_function($query) {
    $evaluator = get_evaluator($query);
    return function ($json_col) use ($query, $evaluator) {
        $item = json_decode($json_col, true);
        #print_r($item);
        #return true;
        [$ok, $dummy] = $evaluator($query, $item);
        return $ok;
    };
}

function get_evaluator($query) {
    #print_r($query);
    $evaluator = function ($query, $item, $level = 0) use (&$evaluator) {
        // dbg('level... ', $level);
        foreach ($query as $q) {
            // dbg("+++ get evaluator level", $level, $q);
            if (!is_assoc($q)) {
                //print "\n\nhuhu\n\n";
                //\dbg('.. klammer', $q);
                [$ok, $next] = $evaluator($q, $item, $level + 1);
            } else {
                $ok = evaluate_single($q['l'], $q['r'], $q['o'], $item);
                $next = $q['x'];
            }

            //   dbg('eval result', $ok, $next);
            if (!$ok && $next == '&&') {
                return [false, $next];
            }
            if ($ok && $next == '||') {
                return [true, $next];
            }
        }
        return [$ok ?? false, null];
    };
    return $evaluator;
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

    $ops = ['==' => 'eq', 'in' => 'in', '!=' => 'ne', '>' => 'gt', '<' => 'lt', '<=' => 'lte', '>=' => 'gte', 'matches' => 'matches'];
    $ops_m = $ops[$op] ?? null;
    if (!$ops_m) {
        return false;
    }

    $cmp = __NAMESPACE__ . '\\' . 'cmp_' . $ops_m;
    if (!function_exists($cmp)) {
        return false;
    }

    return $cmp($l, $r);
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

    if (is_array($data) && !is_assoc($data)) {
        return array_column($data, $current);
    } else {
        return $data[$current] ?? null;
    }
}

function get_literal($data) {
    return $data;
}
