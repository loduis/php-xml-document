<?php

namespace XML\Document;

use DOMDocument;
use Illuminate\Support\{
    Arr
};
use XML\Support\DataAccess;
use XML\Support\EmptyValue;
use XML\Document as Contract;
use XML\Contracts\SingleValue;

class Creator
{
    public $standalone;

    protected $simpleType;

    protected $complexType;

    protected $doc;

    protected $attributes;

    protected $namespaces;

    public function __construct(Contract $doc, array $attributes = [], array $namespaces = [], $standalone = null)
    {
        $this->doc = $doc;
        $this->attributes = $attributes;
        $this->namespaces = $namespaces;
        $this->standalone = $standalone;
    }

    public function toDocument(bool $pretty = false)
    {
        $doc = new DOMDocument('1.0', 'utf-8');
        $doc->preserveWhiteSpace = $pretty;
        $doc->formatOutput = $pretty;
        if ($this->standalone !== null) {
            $doc->xmlStandalone = $this->standalone;
        }
        $this->load($doc, $this->getSource());
        if ($pretty) {
            $source = (string) $doc->saveXML();
            $doc->preserveWhiteSpace = true;
            $doc->formatOutput = false;
            $this->load($doc, $source);
        }

        return $doc;
    }

    private function load(DOMDocument $doc, string $source)
    {
        libxml_use_internal_errors(true);
        if (!$doc->loadXML($source, LIBXML_COMPACT)) {
            $err = libxml_get_last_error();
            libxml_clear_errors();
            throw new \RuntimeException(
                $err->message . ' on line ' . $err->line . ', column ' . $err->column,
                $err->code
            );
        }
    }

    protected function getSource()
    {
        $name = $this->doc->name;
        $root = $this->resolveName($name);
        $attributes = [];
        foreach ($this->attributes as $key => $value) {
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
            $content .= $this->createElementNS($this->simpleType, $key, $this->escape($value), [], $path);
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
        $element = $this->resolveName($name, $path, $namespace);

        return $this->createElement($element, $value, $attributes);
    }

    protected function createElement($element, $value = null, array $attributes = [])
    {
        if ($value !== null) {
            $source = "<$element";
            if ($attributes) {
                array_walk($attributes, function ($value, $key) use (& $source) {
                    if ($value !== null) {
                        $source .= ' ' . $key . '="' . $value . '"';
                    }
                });
            }
            if (is_bool($value)) {
                $value =  $value ? 'true' : 'false';
            }
            $element = $source . ">$value</$element>";
        }

        return $element;
    }

    protected function escape($value)
    {
        if (strpos($value, '<![CDATA[') !== false) {
            return $value;
        }

        return htmlentities($value, ENT_XML1);
    }

    protected function resolveName($name, $path = null, $namespace = null)
    {
        $namespace = $this->resolveNamespace($name, $path, $namespace);

        return $namespace ? $namespace  . ':' . $name : $name;
    }
}
