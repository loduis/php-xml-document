<?php

namespace XML\Document;

use DOMDocument;
use Illuminate\Support\{
    Arr,
    Str
};
use XML\Support\DataAccess;
use XML\Support\EmptyValue;
use XML\Document as Contract;
use XML\Contracts\SingleValue;
use Illuminate\Contracts\Support\Arrayable;

class Creator
{
    public $standalone;

    protected $simpleType;

    protected $complexType;

    protected $doc;

    protected $xmlns;

    protected $namespaces;


    public function __construct(Contract $doc, array $xmlns = [], array $namespaces = [])
    {
        $this->doc = $doc;
        $this->xmlns = $xmlns ;
        $this->namespaces = $namespaces;
    }

    public function toDocument()
    {
        $doc = new DOMDocument('1.0', 'utf-8');
        $doc->loadXML($this->getSource(), LIBXML_COMPACT);
        $doc->preserveWhiteSpace = false;
        if ($this->standalone !== null) {
            $doc->xmlStandalone = $this->standalone;
        }

        return $doc;
    }

    protected function getSource()
    {
        $name = $this->doc->name;
        $root = $this->resolveName($name);
        $attributes = [];
        foreach ($this->xmlns as $key => $value) {
            if (strpos($key, ':') === false) {
                $key = is_numeric($key) ? "xmlns" : "xmlns:$key";
            }
            $attributes[$key] = $value;
        }
        ksort($attributes);

        return '<?xml version="1.0" encoding="utf-8"?>' .
            $this->createElement(
                $root,
                $this->createElements($this->doc, $name),
                $attributes
            );
    }

    protected function createElements($values, $path = null)
    {
        $content = '';
        foreach ($values as $key => $value) {
            $content .= $this->createScalarElement(
                $key,
                $value,
                $path . '/' . $key
            );
        }

        return $content;
    }

    protected function createScalarElement($key, $value, $path)
    {
        $content = '';
        if ($value instanceof DataAccess) {
            if ($value instanceof SingleValue) {
                if ($value->value !== null) {
                    $content .= $this->createElementNS(
                        $this->simpleType,
                        $key,
                        $value->value,
                        $value->attributes(),
                        $path
                    );
                }
            } elseif ($source = $this->createComplexType($key, $value, $path)) {
                $content .= $source;
            }
        } elseif ($value instanceof EmptyValue) {
            $content .= $this->createElementNS($this->complexType, $key, '', [], $path);
        } elseif (is_array($value)) {
            $content .= $this->createComplexArray($key, $value, $path);
        } elseif ($value !== null) {
            $content .= $this->createElementNS($this->simpleType, $key, $value, [], $path);
        }

        return $content;
    }

    protected function createComplexArray($key, $value, $path)
    {
        $content = '';
        if (Arr::isAssoc($value)) {
            if ($source = $this->createComplexType($key, $value, $path)) {
                $content .= $source;
            }
            return $content;
        }
        foreach ($value as $val) {
            $content .= $this->createScalarElement($key, $val, $path);
        }

        return $content;
    }

    protected function createComplexType($key, $value, $path)
    {
        if (($child = $this->createElements($value, $path))) {
            return $this->createElementNS(
                $this->complexType,
                $key,
                $child,
                $value instanceof DataAccess ? $value->attributes() : [],
                $path
            );
        }
    }

    protected function resolveNamespace($key, $path, $default = null)
    {
        return $this->namespaces[$path] ?? $this->namespaces[$key] ?? $default;
    }

    protected function createElementNS($namespace, $name, $value = null, array $attributes = [], $path = null)
    {
        if ($value && $namespace == $this->simpleType && is_string($value) && Str::contains($value, ['&'])) {
            $value = "<![CDATA[$value]]>";
        }
        $element = $this->resolveName($name, $path, $namespace);

        return $this->createElement($element, $value, $attributes);
    }

    protected function createElement($element, $value = null, array $attributes = [])
    {
        if ($value !== null) {
            $source = "<$element";
            if ($attributes) {
                array_walk($attributes, function ($value, $key) use (& $source) {
                    $source .= ' ' . $key . '="' . $value . '"';
                });
            }
            if (is_bool($value)) {
                $value =  $value ? 'true' : 'false';
            }
            $element = $source . ">$value</$element>";
        }

        return $element;
    }

    protected function resolveName($name, $path = null, $namespace = null)
    {
        $namespace = $this->resolveNamespace($name, $path, $namespace);

        return $namespace ? $namespace  . ':' . $name : $name;
    }
}
