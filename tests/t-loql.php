<?php
require 'helper.php';
require_once 'lolql.php';
use function lolql\{parse};

//$test[] = '*(_type == "article" && status != "draft")  order(name, familyname desc number) limit(11) ok ';
$test[] = '😂(_type == "article" && author == "heinz" && (status != "draft" || posted_by == "importer"))  order(name, familyname desc number) limit(11) ok ';
$test[] = 'article() order (created_at)';
$test[] = 'work() order (created_at)';
$test[] = '*() order (created_at)';

$test[] = ' order (created_at)';
$test[] = '*(status.update != "draft" ||    posted_by == "importer 03 ok")';
$test[] = '*(_type ==  "article"  && master.tag[].ref in ["huhu", "ha\"ha", \'she said\', ":\'hi\'"])';
$test[] = '*(_type ==  "article"  && tag in ["huhu", "ok"])
    # order(status)
    limit(8)
';
$test[] = 'article(tag in ["huhu", "ok"])';

//$p = new ParensParser();
//$result = $p->parse($test1);
//print_r($result);
//print_r(parse($test1));

//$p = new ParensParser();
//$result = $p->parse($test2);
//print_r($result);
//print_r(parse($test2));

//print_r(parse($test3));

foreach ($test as $t) {
    print "\n==\n$t\n";
    print_r(parse($t));
    exit;
}
$tt = 'status != "draft" || posted_by == "importer"';
print_r(parse_condition($tt));

exit;

$t = parse_condition('status.update != "draft" ||    posted_by == "importer 03 ok"');

print_r($t);

$t = parse_condition('_type ==  "article"  && master.tag[].ref in ["huhu", "ha\"ha", \'she said\', ":\'hi\'"]');

print_r($t);

$t = parse_condition('"yolo" in com.tags');
print_r($t);

function parse_condition($string) {
    $t = token_get_all('<?' . $string . '?>');
    $t = compact_tokens($t);
    $t = combine_tokens($t);
    return $t;
}

function combine_tokens($t) {
    $buffer = ['l' => ['t' => null, 'v' => []], 'o' => null, 'r' => ['t' => null, 'v' => []], 'x' => null];
    $t = array_reduce($t, function ($res, $item) use (&$buffer) {
        static $lr = 'l';
        if ($item == '&&' || $item == '||') {
            $buffer['x'] = $item;
            $res[] = $buffer;
            $buffer = ['l' => ['t' => null, 'v' => []], 'o' => null, 'r' => ['t' => null, 'v' => []], 'x' => null];
            $lr = 'l';
            return $res;
        }
        if (in_array($item, ['==', 'in', '!=', '>', '<', '<=', '>=', 'contains', 'matches'])) {
            $buffer['o'] = $item;
            $lr = 'r';
        } elseif ($item[0] == '"') {
            $buffer[$lr]['v'][] = trim($item, '"');
            if (!$buffer[$lr]['t']) {
                $buffer[$lr]['t'] = 'v';
            }
        } elseif (!in_array($item, ['[', ']', '.', ','])) {
            $buffer[$lr]['v'][] = $item;
            if (!$buffer[$lr]['t']) {
                $buffer[$lr]['t'] = 'n';
            }
        }
    }, []);
    if ($buffer && $buffer['o']) {
        $t[] = $buffer;
    }
    return $t;
}

function compact_tokens($t) {
    $t = array_map(function ($tok) {
        if (is_array($tok)) {
            return $tok[1] == '<?' || $tok[1] == '?>' ? '' : $tok[1];
        }
        return $tok;
    }, $t);
    $t = array_filter($t, 'trim');
    return $t;
}
