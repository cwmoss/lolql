<?php

namespace cwmoss\lolql;

class limit {

    public ?int $limit = null;
    public int $offset = 0;
    public function __construct(
        public string $source
    ) {
        $this->parse($source);
    }

    public function parse(string $source) {
        $limit_offset = parser::words($source);
        $this->limit = $limit_offset[0] ?? null;
        $this->offset = $limit_offset[1] ?? 0;
    }
}
