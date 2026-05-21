<?php

declare(strict_types=1);

namespace Leamix\CbrPhp\Request;

use DateTimeInterface;

class Ostat implements RequestInterface
{
    use FormatsDate;

    private DateTimeInterface $dateFrom;
    private DateTimeInterface $dateTo;

    public function __construct(DateTimeInterface $dateFrom, DateTimeInterface $dateTo)
    {
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
    }

    public function getEndpoint(): string
    {
        return 'XML_ostat.asp';
    }

    public function getParams(): array
    {
        return [
            'date_req1' => $this->fmt($this->dateFrom),
            'date_req2' => $this->fmt($this->dateTo),
        ];
    }
}
