<?php

namespace cwmoss\lolql;

class literal {

    public int|string $value;
    public $type = "string";

    public function __construct(string|array $value, ?string $quote = null) {
        if ($quote) {
            $this->value = trim($value, $quote);
        } elseif (is_numeric($value)) {
            $this->value = (int) $value;
            $this->type = "int";
        } else {
            $this->value = $value;
            $this->type = "arr";
        }
    }
}
