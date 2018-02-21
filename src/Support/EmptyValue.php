<?php

namespace XML\Support;

class EmptyValue extends \stdClass
{
    public static function newInstance()
    {
        return new static();
    }
}
