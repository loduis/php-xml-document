<?php

namespace XML;

use DOMDocument;
use XML\Document\Creator;
use XML\Document\Contract;

class Document extends DOMDocument
{
    public function __construct(Creator $source)
    {
        parent::__construct('1.0', 'utf-8');
        $this->loadXML((string) $source, LIBXML_COMPACT);
        $this->preserveWhiteSpace = false;
        if ($source->standalone !== null) {
            $this->xmlStandalone = $source->standalone;
        }
    }

    public function asXML()
    {
        return $this->saveXML();
    }

    public function __toString()
    {
        return $this->asXML();
    }

    public function pretty()
    {
        $this->formatOutput = true;

        $content = (string) $this;

        $this->formatOutput = false;

        return $content . PHP_EOL;
    }
}
