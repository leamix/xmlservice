<?php

declare(strict_types=1);

namespace Leamix\CbrPhp\Request;

interface RequestInterface
{
    public function getEndpoint(): string;

    /** @return array<string, string> */
    public function getParams(): array;
}
