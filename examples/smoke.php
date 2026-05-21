<?php

declare(strict_types=1);

use Leamix\CbrPhp\CbrApi;
use Leamix\CbrPhp\Exception\ServiceException;
use Leamix\CbrPhp\Request\Bic;
use Leamix\CbrPhp\Request\Coins;
use Leamix\CbrPhp\Request\Daily;
use Leamix\CbrPhp\Request\DailyEng;
use Leamix\CbrPhp\Request\Depo;
use Leamix\CbrPhp\Request\Dynamic;
use Leamix\CbrPhp\Request\Metal;
use Leamix\CbrPhp\Request\Mkr;
use Leamix\CbrPhp\Request\Ostat;
use Leamix\CbrPhp\Request\Swap;
use Leamix\CbrPhp\Request\Val;
use Leamix\CbrPhp\Request\ValFull;

$autoload = dirname(__DIR__) . '/vendor/autoload.php';
if (!is_file($autoload)) {
    fwrite(STDERR, "Install dependencies first: composer install\n");
    exit(2);
}

require_once $autoload;

$api = new CbrApi();

$today = new DateTimeImmutable('today');
$from = new DateTimeImmutable('2024-01-01');
$to = new DateTimeImmutable('2024-01-31');

$requests = [
    'Daily latest' => new Daily(),
    'Daily by date' => new Daily($today),
    'DailyEng by date' => new DailyEng($today),
    'Val' => new Val(),
    'ValFull' => new ValFull(),
    'Dynamic USD' => new Dynamic($from, $to, 'R01235'),
    'Metal' => new Metal($from, $to),
    'Ostat' => new Ostat($from, $to),
    'Mkr' => new Mkr($from, $to),
    'Depo' => new Depo($from, $to),
    'Swap' => new Swap($from, $to),
    'Bic by code' => new Bic('', '044525225'),
    'Bic by name' => new Bic('Сбербанк'),
    'Coins' => new Coins($from, $to),
];

$failures = 0;

foreach ($requests as $name => $request) {
    try {
        $xml = $api->sendRaw($request);
        $root = getRootElementName($xml);
        $count = countTopLevelElements($xml);

        printf(
            "[OK] %-16s root=%-12s children=%-3d bytes=%-6d\n",
            $name,
            $root,
            $count,
            strlen($xml),
        );
    } catch (ServiceException $e) {
        $failures++;
        printf("[FAIL] %-16s %s\n", $name, $e->getMessage());
    }
}

exit($failures > 0 ? 1 : 0);

function getRootElementName(string $xml): string
{
    $previous = libxml_use_internal_errors(true);
    $element = simplexml_load_string($xml);
    libxml_clear_errors();
    libxml_use_internal_errors($previous);

    return $element instanceof SimpleXMLElement ? $element->getName() : 'invalid-xml';
}

function countTopLevelElements(string $xml): int
{
    $previous = libxml_use_internal_errors(true);
    $element = simplexml_load_string($xml);
    libxml_clear_errors();
    libxml_use_internal_errors($previous);

    return $element instanceof SimpleXMLElement ? $element->count() : 0;
}
