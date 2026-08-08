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
        public array $conditions = [],
        public ?order $order = null,
        public ?limit $limit = null,
        public ?projection $projection = null,
        public bool $count = false,
        public bool $preview = false
    ) {
    }

    // TODO: slice/ limit
    public function query(array $ds, array $params = []): array {
        // TODO: params aus dem parsing herausnehmen und zum evaluierungszeitpunkt einfügen
        $rs = $this->eval_cond($ds, $params);

        if ($this->order) {
            usort($rs, $this->order->fun);
        }

        return array_values($rs);
    }

    public function eval_cond(array $db, array $params = []): array {
        $evaluator = $this->get_evaluator();
        $query = $this->conditions;
        return array_filter($db, static function ($item) use ($query, $evaluator, $params) {
            // dbg('item-compare...', $item['_id'], $item['title']);
            [$ok, $next] = $evaluator($query, $item, $params);
            return $ok;
        });
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

    public function get_evaluator(): Closure {
        #print_r($query);
        $evaluator = function (array $query, array $item, array $params = [], int $level = 0) use (&$evaluator) {
            // dbg('level... ', $level);
            // empty condition => fits all
            if (!$query) return [true, null];
            foreach ($query as $q) {
                // dbg("+++ get evaluator level", $level, $q);
                if (!is_object($q)) {
                    //print "\n\nhuhu\n\n";
                    //\dbg('.. klammer', $q);
                    [$ok, $next] = $evaluator($q, $item, $params, $level + 1);
                } else {
                    $ok = $q->eval($item, $params);
                    $next = $q->next;
                }

                //   dbg('eval result', $ok, $next);
                if (!$ok && $next == logic_operator::and) {
                    return [false, $next];
                }
                if ($ok && $next == logic_operator::or) {
                    return [true, $next];
                }
            }
            return [$ok ?? false, null];
        };
        return $evaluator;
    }
}
