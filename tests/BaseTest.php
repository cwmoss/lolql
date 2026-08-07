<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use cwmoss\lolql\parser;

final class BaseTest extends TestCase {

    /*
    public function testdata(): document {
        $p = new parser(file_get_contents(__DIR__ . "/data.json"));
        return $p->parse();
    }
    */

    public function testParse(): void {
        $q = new parser()->parse('q(name=="luisa" && city=="leipzig")');
        $this->assertEquals(null, $q->limit);
        $this->assertEquals(null, $q->order);
        $this->assertEquals(null, $q->projection);
        $this->assertNotEquals(null, $q->limit);
    }
}
