<?php

namespace XML\Support;

trait SingleValue
{
    public function __construct($value = [], array $attributes = [])
    {
        if (is_scalar($value)) {
            $value = ['value' => $value];
        }
        if ($attributes) {
            $this->attributes($attributes);
        }

        parent::__construct($value);
    }

    public function toArray()
    {
        return [
            $this->value
        ];
    }

    public function __toString()
    {
        return (string) $this->value;
    }
}
