<?php

declare(strict_types=1);

namespace Leamix\CbrPhp\Http;

use Leamix\CbrPhp\Exception\ServiceException;

interface HttpClientInterface
{
    /**
     * @throws ServiceException on connection failure or non-2xx HTTP status
     */
    public function get(string $url): string;
}
