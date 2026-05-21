<?php

declare(strict_types=1);

namespace Leamix\CbrPhp\Request;

class Bic implements RequestInterface
{
    private string $name;
    private string $bic;

    public function __construct(string $name = '', string $bic = '')
    {
        $this->name = $name;
        $this->bic = $bic;
    }

    public function getEndpoint(): string
    {
        return 'XML_bic.asp';
    }

    public function getParams(): array
    {
        return array_filter(
            ['name' => $this->name, 'bic' => $this->bic],
            static fn(string $v): bool => $v !== '',
        );
    }
}
