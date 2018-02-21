<?php

namespace XML\Support;

use Ubl\Support;
use Ubl\Contracts;
use Illuminate\Support\Arr;

class Single extends DataAccess implements Contracts\SingleValue
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
