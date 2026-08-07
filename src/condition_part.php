<?php

namespace cwmoss\lolql;

class condition_part {

    // type k key v value
    public function __construct(
        public ?string $type = null,
        public array $content = [],
    ) {
    }

    public bool $is_key {
        get => $this->type == "k";
    }

    public bool $is_value {
        get => $this->type == "v";
    }

    public function update_type_key() {
        if (!$this->type) $this->type = "k";
    }

    public function update_type_value() {
        if (!$this->type) $this->type = "v";
    }

    public function get_value(mixed $data) {
        if ($this->type == 'k') {
            return self::resolve_value($this->content, $data);
        } else {
            return $this->content;
        }
    }

    static public function resolve_value(array $keys, mixed $data): mixed {
        if (!$data) return null;

        $current = array_shift($keys);

        // nested?
        if ($keys) {
            return self::resolve_value($keys, $data[$current] ?? null);
        }

        if (is_array($data) && !self::is_assoc($data)) {
            return array_column($data, $current);
        } else {
            return $data[$current] ?? null;
        }
    }

    static public function is_assoc(array $arr): bool {
        if ([] === $arr) {
            return false;
        }
        // return array_keys($arr) !== range(0, count($arr) - 1);
        return !array_is_list($arr);
    }
}
