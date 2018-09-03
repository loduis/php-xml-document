<?php

namespace XML\Document;


abstract class Contract extends Element
{
    public static function fromArray(array $data)
    {
        return new static($data);
    }

    public static function fromJson($json)
    {
        return static::fromArray(json_decode($json, true));
    }

    public function fromFile($path)
    {
        return static::fromJson(file_get_contents($path));
    }

    protected function init($data, $fillable)
    {
        $this->fillable += $fillable;
        parent::__construct($data);
    }

    protected function getName()
    {
        $className = static::class;
        $className = str_replace('\\', '/', $className);

        return basename($className);
    }
}
