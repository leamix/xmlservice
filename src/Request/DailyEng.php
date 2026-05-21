<?php

declare(strict_types=1);

namespace Leamix\XmlService\Request;

use DateTimeInterface;

class DailyEng implements RequestInterface
{
    use FormatsDate;

    private ?DateTimeInterface $date;

    public function __construct(?DateTimeInterface $date = null)
    {
        $this->date = $date;
    }

    public function getEndpoint(): string
    {
        return 'XML_daily_eng.asp';
    }

    public function getParams(): array
    {
        return $this->date !== null ? ['date_req' => $this->fmt($this->date)] : [];
    }
}
