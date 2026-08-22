<?php

namespace cwmoss\lolql;

use PhpToken;

class literal {

    public int|string|array $value;
    public $type = "string";

    public function __construct(string|array|int $value, ?string $quote = null) {
        if ($quote) {
            $this->value = trim($value, $quote);
        } elseif (is_numeric($value)) {
            $this->value = (int) $value;
            $this->type = "int";
        } else {
            $this->value = $value;
            if (is_array($value)) $this->type = "arr";
        }
    }

    public function add_item(PhpToken $token) {
        $txt = $token->text;
        $this->value[] =
            match ($token->id) {
                \T_CONSTANT_ENCAPSED_STRING => new literal($txt, $txt[0])->value,
                \T_LNUMBER => (int) $txt,
                default => throw new syntax_exception("unkown array token ({$txt})", $token, "")
            };
    }
}
