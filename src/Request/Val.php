<?php

declare(strict_types=1);

namespace Leamix\XmlService\Request;

class Val implements RequestInterface
{
    public function getEndpoint(): string
    {
        return 'XML_val.asp';
    }

    public function getParams(): array
    {
        return [];
    }
}
