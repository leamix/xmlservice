<?php

declare(strict_types=1);

namespace Leamix\CbrPhp\Request;

use DateTimeInterface;

class Dynamic implements RequestInterface
{
    use FormatsDate;

    private DateTimeInterface $dateFrom;
    private DateTimeInterface $dateTo;
    private string $valNmRq;

    public function __construct(DateTimeInterface $dateFrom, DateTimeInterface $dateTo, string $valNmRq)
    {
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
        $this->valNmRq = $valNmRq;
    }

    public function getEndpoint(): string
    {
        return 'XML_dynamic.asp';
    }

    public function getParams(): array
    {
        return [
            'date_req1' => $this->fmt($this->dateFrom),
            'date_req2' => $this->fmt($this->dateTo),
            'VAL_NM_RQ' => $this->valNmRq,
        ];
    }
}
