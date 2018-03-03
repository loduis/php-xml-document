<?php

namespace XML\Support;

class Single extends DataAccess implements \XML\Contracts\SingleValue
{
    use SingleValue;

    protected $fillable = [
        'value' => 'mixed'
    ];

    protected function getValue($value)
    {
        return $value;
    }
}
