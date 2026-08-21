<?php

require_once(__DIR__ . "/vendor/autoload.php");

use cwmoss\lolql\parser;
use cwmoss\lolql\tokenizer;

$s = '*(name=="ott\"o" || (adr.hnr==23 && 
# auch anna
name=="anna")) .. ?nice ... 
order(date desc, id)';
$t = token_get_all('<?php ' . $s . ' ?>');
// print "with comments\n";
print_r($t);
$p = new parser()->parse($s);

//$p = new tokenizer($s);
//$ts = $p->tokenize();
print_r($p);
exit;
$p = new parser()->parse('*(name=="otto" || nr==23)');
print_r($p);

$p = new parser()->parse("*(name=='otto' || nr==23)");
print_r($p);

$p = new parser()->parse("*(name=='otto' || nr==\$haus_nummer)");
print_r($p);

$p = new parser()->parse('*(name=="otto" || (adr.hnr==23 && name="anna"))');
print_r($p);

$p = new parser()->parse('*(bling notnull && visits>"20")');
print_r($p);

$p = new parser()->parse('*(page->is_fresh==true ||is_fresh==#false )');
print_r($p);

$p = new parser()->parse('*(page.title==$title)');
print_r($p);

$p = new parser()->parse('*()');
print_r($p);


foreach ($ts as $token) {
    echo "Line {$token->line}: {$token->getTokenName()} ('{$token->text}')", PHP_EOL;
}
exit;

$p = new parser()->parse('*(tags in ["otto", "anna"])');
print_r($p);

$p = new parser()->parse('*(author[].tags in ["otto", "anna"])');
print_r($p);
