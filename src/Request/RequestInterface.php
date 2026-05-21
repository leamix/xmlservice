<?php

declare(strict_types=1);

namespace Leamix\XmlService\Request;

interface RequestInterface
{
    public function getEndpoint(): string;

    /** @return array<string, string> */
    public function getParams(): array;
}
