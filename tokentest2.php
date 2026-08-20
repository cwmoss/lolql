<?php
require_once(__DIR__ . "/vendor/autoload.php");

use cwmoss\lolql\ast;

$code = '<?php $summe 444 4.5 -4.6 "hey" \'joe\' !(_type!="post" || count(m.*.tags)>3 ?>';
$tokens = PhpToken::tokenize($code);

foreach ($tokens as $token) {
    echo "Line {$token->line}: {$token->getTokenName()} ('{$token->text}')", PHP_EOL;
}

$code = "*(!(_type=='post' && post.id=='44') || text isnull) order(pub_date)";
//$code = "*(_type=='post' && post.id=='44' || text isnull)";
$ast = new ast($code);

print_r($ast);
