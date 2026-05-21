<?php

declare(strict_types=1);

namespace Leamix\CbrPhp\Request;

use DateTimeInterface;

trait FormatsDate
{
    protected function fmt(DateTimeInterface $date): string
    {
        return $date->format('d/m/Y');
    }
}
