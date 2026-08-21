<?php

namespace cwmoss\lolql;

enum operator: string {

    case eq = "==";
    case eqt = "===";
    case neq = "!=";
    case gt = ">";
    case lt = "<";
    case gte = ">=";
    case lte = "<=";
    case in = "in";
    case not = "!";
    case matches = "matches";
    case notnull = "notnull";
    case isnull = "isnull";
    case call = "call";
    case and = "&&";
    case or = "||";
    case concat = "~";

    static public function parse(string $source): ?self {
        $source = strtolower($source);
        return self::tryFrom($source);
    }
}
