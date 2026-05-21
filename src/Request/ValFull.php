<?php

declare(strict_types=1);

namespace Leamix\XmlService\Request;

class ValFull implements RequestInterface
{
    public function getEndpoint(): string
    {
        return 'XML_valFull.asp';
    }

    public function getParams(): array
    {
        return [];
    }
}
