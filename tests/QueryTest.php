<?php

declare(strict_types=1);

use cwmoss\lolql\lolql;
use PHPUnit\Framework\TestCase;
use cwmoss\lolql\parser;

final class QueryTest extends TestCase {

    public $testdata = [
        ['_id' => 'a1', 'title' => 'hey', 'status' => 'draft', 'visits' => 20, 'authors' => [['_ref' => '2'], ['_ref' => '4']]],
        ['_id' => 'a2', 'title' => 'hello', 'status' => 'published', 'tags' => ['hot', 'cold'], 'visits' => 200],
        ['_id' => 'a3', 'title' => 'world', 'status' => 'published', 'tags' => ['hot'], 'visits' => 5],
        ['_id' => 'a4', 'title' => 'hello world', 'status' => 'published', '_type' => 'article'],
        ['_id' => 'a5', 'title' => 'world is caos', 'status' => 'draft', '_type' => 'article', 'tags' => ['blue']],
        ['_id' => 'a6', 'title' => 'yourworld is caos', 'status' => 'published', 'visits' => 55],
        ['_id' => 'a7', 'title' => 'worldwideweb', 'status' => 'waiting', 'visits' => 14],
    ];

    public function testAll(): void {
        $q = '*()';
        $res = new lolql($q)->run($this->testdata);
        $this->assertEquals(7, count($res));
    }
    public function testEquals(): void {
        $q = 'article()';
        $res = new lolql($q)->run($this->testdata);
        $this->assertEquals(2, count($res));

        $q = 'article( status=="draft" )';
        $res = new lolql($q)->run($this->testdata);
        $this->assertEquals(1, count($res));

        $q = 'article( status != "trash" )';
        $res = new lolql($q)->run($this->testdata);
        $this->assertEquals(2, count($res));
    }

    public function testMatches(): void {
        $q = '*(title matches "hello")';
        $res = new lolql($q)->run($this->testdata);
        $this->assertEquals(2, count($res));

        $q = '*(title matches "*hello")';
        $res = new lolql($q)->run($this->testdata);
        $this->assertEquals(1, count($res));

        $q = '*(title matches "hello" || (status == "draft" && _id == "a5"))';
        $res = new lolql($q)->run($this->testdata);
        $this->assertEquals(3, count($res));
    }

    public function testIn(): void {
        $q = '*(status in ["draft", "waiting"])';
        $res = new lolql($q)->run($this->testdata);
        $this->assertEquals(3, count($res));
        // print_r($res);
        $this->assertEquals("a7", $res[2]["_id"]);

        $q = '*("cold" in tags)';
        $res = new lolql($q)->run($this->testdata);
        // print_r($res);
        $this->assertEquals(1, count($res));
    }

    public function testLtGt(): void {
        $q = '*(_id < "a2")';
        $res = new lolql($q)->run($this->testdata);
        $this->assertEquals(1, count($res));

        $q = '*(_id > "a2")';
        $res = new lolql($q)->run($this->testdata);
        // print_r($res);
        $this->assertEquals(5, count($res));
    }

    public function testOrder(): void {
        $q = 'article() order(_id desc)';
        $res = new lolql($q)->run($this->testdata);
        $this->assertEquals(2, count($res));
        // print_r($res);
        $this->assertEquals("a5", $res[0]["_id"]);

        $q = 'article() order(_id)';
        $res = new lolql($q)->run($this->testdata);
        $this->assertEquals(2, count($res));
        // print_r($res);
        $this->assertEquals("a4", $res[0]["_id"]);
    }

    public function testNumber(): void {
        $q = new lolql('*(bling isnull && visits>20)');
        $res = $q->run($this->testdata);
        // print_r($q);
        $this->assertEquals(2, count($res));
        $q = '*(visits>=20)';
        $res = new lolql($q)->run($this->testdata);
        // print_r($res);
        $this->assertEquals(3, count($res));
    }

    public function testNull(): void {
        $q = new lolql('*(bling notnull)');
        $res = $q->run($this->testdata);
        // print_r($q);
        $this->assertEquals(0, count($res));
        $q = new lolql('*(authors isnull)');
        $res = $q->run($this->testdata);
        // print_r($q);
        $this->assertEquals(6, count($res));
    }

    public function testArray(): void {
        $q = '*(authors._ref=="4")';
        // $q = '*(authors._ref==4)'; // fails
        $res = new lolql($q)->run($this->testdata);
        // print_r($res);
        $this->assertEquals(1, count($res));

        $q = '*(authors._ref==4)';
        $res = new lolql($q)->run($this->testdata);
        // print_r($res);
        $this->assertEquals(1, count($res));
    }

    public function testParams(): void {
        $q = new lolql('*(authors._ref==$id)');
        $res = $q->run($this->testdata, ["id" => "44"]);
        // print_r($q);
        $this->assertEquals(0, count($res));

        $res = $q->run($this->testdata, ["id" => "4"]);
        // print_r($res);
        $this->assertEquals(1, count($res));
    }

    // '*(title[] matches "hello" || (pub.status == $status && _id == 55) || date(publ) > now()) order(name age)',
    /*
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
*/
}
