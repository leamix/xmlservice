<?php

declare(strict_types=1);

namespace Leamix\CbrPhp\Request;

use DateTimeInterface;

class Daily implements RequestInterface
{
    use FormatsDate;

    private ?DateTimeInterface $date;

    public function __construct(?DateTimeInterface $date = null)
    {
        $this->date = $date;
    }

    public function getEndpoint(): string
    {
        return 'XML_daily.asp';
    }

    public function getParams(): array
    {
        return $this->date !== null ? ['date_req' => $this->fmt($this->date)] : [];
    }
}
