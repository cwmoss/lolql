<?php

namespace cwmoss\lolql;

enum operator {
    case eq;
    case neq;
    case gt;
    case lt;
    case gte;
    case lte;
    case in;
    case matches;

    static function parse(string $source): self {
        // '==', 'in', '!=', '>', '<', '<=', '>=', 'matches'
        return match ($source) {
            '==' => self::eq,
            '!=' => self::neq,
            '>' => self::gt,
            '>=' => self::gte,
            '<' => self::lt,
            '<=' => self::lte,
            'in' => self::in,
            'matches' => self::matches,
        };
    }
}
