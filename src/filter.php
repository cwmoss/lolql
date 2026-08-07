<?php

namespace cwmoss\lolql;


/*
    query represents a parsed lolql query string
*/

class query {

    public function __construct(
        public ?filter $filter = null,
        public ?order $order = null,
        public ?limit $limit = null,
        public ?projection $projection = null
    ) {
    }
}
