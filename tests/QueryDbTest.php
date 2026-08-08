<?php

declare(strict_types=1);

use cwmoss\lolql\lolql;
use PHPUnit\Framework\TestCase;
use cwmoss\lolql\parser;

final class QueryDbTest extends TestCase {

    public $testdata = [
        ['_id' => 'a1', '_type' => 'x', 'title' => 'hey', 'status' => 'draft', 'authors' => [['_ref' => '2'], ['_ref' => '4']]],
        ['_id' => 'a2', '_type' => 'y', 'title' => 'hello', 'status' => 'published', 'tags' => ['hot', 'cold']],
        ['_id' => 'a3', '_type' => 'z', 'title' => 'world', 'status' => 'published', 'tags' => ['hot']],
        ['_id' => 'a4', 'title' => 'hello world', 'status' => 'published', '_type' => 'article'],
        ['_id' => 'a5', 'title' => 'world is caos', 'status' => 'draft', '_type' => 'article', 'tags' => ['blue']],
        ['_id' => 'a6', '_type' => 'x', 'title' => 'yourworld is caos', 'status' => 'published'],
        ['_id' => 'a7', '_type' => 'y', 'title' => 'worldwideweb', 'status' => 'waiting'],
    ];

    public function make_db() {
        $db = PDO::connect("sqlite::memory:");
        $db->exec(lolql::basic_schema());
        foreach ($this->testdata as $d) {
            $j = json_encode($d, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $db->exec("INSERT INTO docs (body) VALUES('$j')");
        }
        return $db;
    }
    public function testEquals(): void {
        $db = $this->make_db();
        $q = 'article()';
        [$res, $info] = new lolql($q)->run_pdo($db);
        $this->assertEquals(2, count($res));

        $q = 'article( status=="draft" )';
        [$res, $info] = new lolql($q)->run_pdo($db);
        // print_r($res);
        $this->assertEquals(1, count($res));

        $q = 'article( status != "trash" )';
        [$res, $info] = new lolql($q)->run_pdo($db);
        $this->assertEquals(2, count($res));
    }

    public function testMatches(): void {
        $db = $this->make_db();
        $q = '*(title matches "hello")';
        [$res, $info] = new lolql($q)->run_pdo($db);
        $this->assertEquals(2, count($res));

        $q = '*(title matches "*hello")';
        [$res, $info] = new lolql($q)->run_pdo($db);
        $this->assertEquals(1, count($res));

        $q = '*(title matches "hello" || (status == "draft" && _id == "a5"))';
        [$res, $info] = new lolql($q)->run_pdo($db);
        $this->assertEquals(3, count($res));
    }

    public function testIn(): void {
        $db = $this->make_db();
        $q = '*(status in ["draft", "waiting"])';
        [$res, $info] = new lolql($q)->run_pdo($db);
        $this->assertEquals(3, count($res));
        // print_r($res);
        $this->assertEquals("a7", $res[2]["_id"]);

        $q = '*("cold" in tags)';
        [$res, $info] = new lolql($q)->run_pdo($db);
        // print_r($res);
        $this->assertEquals(1, count($res));
    }

    public function testOrder(): void {
        $db = $this->make_db();
        $q = 'article() order(_id desc)';
        [$res, $info] = new lolql($q)->run_pdo($db);
        $this->assertEquals(2, count($res));
        // print_r($res);
        $this->assertEquals("a5", $res[0]["_id"]);

        $q = 'article() order(_id)';
        [$res, $info] = new lolql($q)->run_pdo($db);
        $this->assertEquals(2, count($res));
        // print_r($res);
        $this->assertEquals("a4", $res[0]["_id"]);
    }

    public function testArray(): void {
        $db = $this->make_db();
        $q = '*(authors._ref=="4")';
        // $q = '*(authors._ref==4)'; // fails
        [$res, $info] = new lolql($q)->run_pdo($db);
        // print_r($res);
        $this->assertEquals(1, count($res));
    }
}
