<?php

namespace cwmoss\lolql;

use Closure;

/**
 *    query represents a parsed lolql query string
 */
class query {

    public function __construct(
        /**
         * conditions items can be
         *  - a condition 
         *  - a (sub) array 
         *      ... and so on recursive
         */
        public node $ast,
        public ?order $order = null,
        public ?limit $limit = null,
        public ?projection $projection = null,
        public ?string $type = null,
        public bool $count = false,
        public bool $preview = false
    ) {
    }

    static function new_from_ast(node $ast, string $type): self {
        $q = new self($ast);
        if (!(in_array($type, ['q', '*', '😂', '❤️']))) {
            $q->type = $type;
        }
        return $q;
    }
    // TODO: slice/ limit
    public function query(array $ds, array $params = []): array {
        // TODO: params aus dem parsing herausnehmen und zum evaluierungszeitpunkt einfügen
        $rs = evaluator::filter_dataset($this->ast, $ds, $params);

        if ($this->order) {
            usort($rs, $this->order->fun);
        }

        return array_values($rs);
    }

    public function limit_one(): self {
        $this->limit = new limit("1");
        return $this;
    }

    public function eval_cond_as_sql_function(array $params = []): Closure {
        $evaluator = $this->get_evaluator();
        $query = $this->conditions;
        return static function ($json_col) use ($params, $query, $evaluator) {
            $item = json_decode($json_col, true);
            #print_r($item);
            #return true;
            [$ok, $dummy] = $evaluator($query, $item, $params);
            return $ok;
        };
    }
}
