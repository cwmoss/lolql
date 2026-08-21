<?php
require_once(__DIR__ . "/vendor/autoload.php");

use cwmoss\lolql\parser;
use cwmoss\lolql\evaluator;
use cwmoss\lolql\operator;
use cwmoss\lolql\lolql;
use cwmoss\lolql\printer;

$code = '<?php $summe 444 4.5 -4.6 "hey" \'joe\' !(_type!="post" || count(m.*.tags)>3 ?>';
$tokens = PhpToken::tokenize($code);

foreach ($tokens as $token) {
    echo "Line {$token->line}: {$token->getTokenName()} ('{$token->text}')", PHP_EOL;
}

$code = "*(!(_type=='post' && post.id=='44') || count(text) > 4) order(pub_date asc)";
$code = "*(!(_type=='post') || size > 4 || title matches 'super') order(pub_date asc)";
//$code = "*(_type=='post' && post.id=='44' || text isnull)";
$q = new parser($code)->parse_query();

print_r($q);
print new printer()->print($q->ast);
exit;
$e = operator::call;
// var_dump($e instanceof operator);

$data = [[
    "_type" => "post",
    "size" => 2,
    "title" => "alles super"
], [
    "_type" => "post",
    "size" => 6,
    "title" => "alles doof"
]];

$ev = evaluator::make_function($q->ast);
$res = $ev($data[0]);
var_dump($res);

$res = evaluator::filter_dataset($q, $data);
print_r($res);

$q = new lolql('*(_id matches "a*" && (_id=="a2" || _id=="a3") && _id=="a4")');
print_r($q);

// print evaluator::evaluate_dbg($q->query->ast);
