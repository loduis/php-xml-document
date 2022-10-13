<?php

namespace XML;

use XML\Document\{
    Element
};

abstract class Document extends Element
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

    abstract protected function creator();

    public function create()
    {
        return $this->creator()->toDocument();
    }

    public function pretty()
    {
        $doc = $this->create();

        $doc->formatOutput = true;

        $content = (string) $doc->saveXML();

        $doc->formatOutput = false;

        return $content . PHP_EOL;
    }

    public function validate(string $filename): bool
    {
        return $this->create()->schemaValidate($filename);
    }

    public function __toString()
    {
        return $this->create()->saveXML();
    }
}
