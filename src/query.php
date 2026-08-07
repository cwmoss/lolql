<?php

namespace cwmoss\lolql;


/*
    query represents a parsed lolql query string
*/

class query {

    public function __construct(
        /**
         * conditions items can be
         *  - a condition 
         *  - a (sub) array 
         *      ... and so on recursive
         */
        public array $conditions = [],
        public ?order $order = null,
        public ?limit $limit = null,
        public ?projection $projection = null,
        public bool $count = false,
        public bool $preview = false
    ) {
    }
}
