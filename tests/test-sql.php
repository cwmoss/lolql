<?php
$loader = require(__DIR__.'/vendor/autoload.php');

$q = "select json_extract(body, '$.works') as W, json_extract(body, '$.familyname') as F from docs limit 3";
$name = "slowfoot.db";

$db = \ParagonIE\EasyDB\Factory::fromArray([
    "sqlite:$name"
]);

$res = $db->safeQuery($q, []);
print_r($res);
foreach($res as $r){
    print_r(json_decode($r['W'], true));
    print "{$r['F']}\n";
    print_r(json_decode($r['F'], true));

}