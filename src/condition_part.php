<?php

namespace cwmoss\lolql;

class condition_part {

    // type k key v value
    public function __construct(
        public ?string $type = null,
        public mixed $content = null,
    ) {
    }
}
