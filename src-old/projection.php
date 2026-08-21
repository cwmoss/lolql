<?php

namespace cwmoss\lolql;

class projection {

    public function __construct(
        public string $source
    ) {
        $this->parse($source);
    }

    public function parse(string $source) {
    }
}
