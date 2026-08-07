<?php
/*
lolql - lovely query language

make queries easy & keep it simple
*/
// https://www.sanity.io/docs/content-lake/query-cheat-sheet

namespace cwmoss\lolql;

class lolql {

    public query $query;

    public function __construct(string $query) {
        $this->query = new parser($query)->parse($query);
    }

    public function run(array $data): array {
        return $this->query->query($data);
    }
}
