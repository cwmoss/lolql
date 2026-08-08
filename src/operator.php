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
    case notnull;
    case isnull;

    static function parse(string $source): self {
        return match (strtolower($source)) {
            '==' => self::eq,
            '!=' => self::neq,
            '>' => self::gt,
            '>=' => self::gte,
            '<' => self::lt,
            '<=' => self::lte,
            'in' => self::in,
            'matches' => self::matches,
            'notnull' => self::notnull,
            'isnull' => self::isnull,
        };
    }
}
