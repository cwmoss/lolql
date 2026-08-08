<?php
/*
lolql - lovely query language

make queries easy & keep it simple
*/
// https://www.sanity.io/docs/content-lake/query-cheat-sheet

namespace cwmoss\lolql;

use PDO;

class lolql {

    public query $query;

    public function __construct(string $query) {
        $this->query = new parser($query)->parse($query);
    }

    public function run(array $data): array {
        return $this->query->query($data);
    }

    public function make_sql(): sql_query {
        return new sql_query($this->query);
    }

    public function run_pdo(PDO\Sqlite $db): array {
        $q = $this->make_sql();

        $db->createFunction($q->fun_name, $q->fun, 1);

        $res = self::run_sql($db, $q->sql);
        $res = $q->decode_result($res);

        $pageinfo = [];
        if ($q->count_sql) {
            if ($q->limited) {
                $total = self::run_sql_column($db, $q->count_sql);
            } else {
                $total = count($res);
            }
            $pageinfo = ['total' => $total];
        }
        return [$res, $pageinfo];
    }

    static public function run_sql(PDO $pdo, string $q): array {
        $fetchStyle = PDO::FETCH_ASSOC;
        $stmt = $pdo->prepare($q);
        $stmt->execute();
        $results = $stmt->fetchAll($fetchStyle);
        return $results;
    }

    static public function run_sql_column(PDO $pdo, string $q): mixed {
        $stmt = $pdo->prepare($q);
        $stmt->execute();
        return $stmt->fetchColumn(0);
    }

    static public function basic_schema(): string {
        return <<<DDL
CREATE TABLE IF NOT EXISTS docs (
    body JSON,
    _id TEXT GENERATED ALWAYS AS (json_extract(body, '$._id'))
        VIRTUAL
        NOT NULL
        ,
    _type TEXT GENERATED ALWAYS AS (json_extract(body, '$._type'))
        VIRTUAL
        NOT NULL
)
DDL;
    }
}
