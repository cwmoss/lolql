<?php

namespace cwmoss\lolql;

enum operator: string {

    case eq = "==";
    case neq = "!=";
    case gt = ">";
    case lt = "<";
    case gte = ">=";
    case lte = "<=";
    case in = "in";
    case matches = "matches";
    case notnull = "notnull";
    case isnull = "isnull";

    static public function parse(string $source): ?self {
        $source = strtolower($source);
        return self::tryFrom($source);
    }
}
