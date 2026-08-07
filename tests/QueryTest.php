<?php

declare(strict_types=1);

use cwmoss\lolql\lolql;
use PHPUnit\Framework\TestCase;
use cwmoss\lolql\parser;

final class QueryTest extends TestCase {

    public $testdata = [
        ['_id' => 'a1', 'title' => 'hey', 'status' => 'draft', 'authors' => [['_ref' => '2'], ['_ref' => '4']]],
        ['_id' => 'a2', 'title' => 'hello', 'status' => 'published', 'tags' => ['hot', 'cold']],
        ['_id' => 'a3', 'title' => 'world', 'status' => 'published', 'tags' => ['hot']],
        ['_id' => 'a4', 'title' => 'hello world', 'status' => 'published', '_type' => 'article'],
        ['_id' => 'a5', 'title' => 'world is caos', 'status' => 'draft', '_type' => 'article', 'tags' => ['blue']],
        ['_id' => 'a6', 'title' => 'yourworld is caos', 'status' => 'published'],
        ['_id' => 'a7', 'title' => 'worldwideweb', 'status' => 'waiting'],
    ];

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

    public function testOrder(): void {
        $q = 'article() order(_id desc)';
        $res = new lolql($q)->run($this->testdata);
        $this->assertEquals(2, count($res));
        // print_r($res);
        $this->assertEquals("a5", $res[0]["_id"]);
    }
}
