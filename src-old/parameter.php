<?php

namespace cwmoss\lolql;

class parameter {

    public string $name;

    public function __construct(string $name) {
        $this->name = ltrim($name, '$');
    }
}
