<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';

use Leamix\XmlService\CbrApi;
use Leamix\XmlService\Exception\ServiceException;
use Leamix\XmlService\Request\Bic;
use Leamix\XmlService\Request\Coins;
use Leamix\XmlService\Request\Daily;
use Leamix\XmlService\Request\DailyEng;
use Leamix\XmlService\Request\Depo;
use Leamix\XmlService\Request\Dynamic;
use Leamix\XmlService\Request\Metal;
use Leamix\XmlService\Request\Mkr;
use Leamix\XmlService\Request\Ostat;
use Leamix\XmlService\Request\Swap;
use Leamix\XmlService\Request\Val;
use Leamix\XmlService\Request\ValFull;

function section(string $title): void
{
    $line = str_repeat('─', 60);
    echo "\n{$line}\n  {$title}\n{$line}\n";
}

function ok(string $msg): void
{
    echo "  [OK]   {$msg}\n";
}

function fail(string $msg): void
{
    echo "  [FAIL] {$msg}\n";
}

function info(string $msg): void
{
    echo "         {$msg}\n";
}

$today = new DateTimeImmutable('today');
$weekAgo = new DateTimeImmutable('-7 days');
$monthAgo = new DateTimeImmutable('-30 days');
$jan1 = new DateTimeImmutable('2024-01-01');
$jan31 = new DateTimeImmutable('2024-01-31');

$cbr = new CbrApi();
$errors = 0;

section('Daily — today\'s currency rates');

try {
    $data = $cbr->send(new Daily($today));
    info('Date       : ' . ($data['@attributes']['Date'] ?? '—'));
    info('Currencies : ' . count($data['Valute'] ?? []));
    foreach (['USD', 'EUR', 'CNY'] as $code) {
        foreach ($data['Valute'] ?? [] as $v) {
            if (($v['CharCode'] ?? '') === $code) {
                info(sprintf('  %-4s  %s RUB', $code, $v['Value']));
                break;
            }
        }
    }
} catch (ServiceException $e) {
    fail($e->getMessage());
    $errors++;
}

section('Daily — raw XML response');

try {
    $xml = $cbr->sendRaw(new Daily($today));
    ok(strlen($xml) . ' bytes received');
    info(substr($xml, 0, 160) . ' …');
} catch (ServiceException $e) {
    fail($e->getMessage());
    $errors++;
}

section('DailyEng — rates in English');

try {
    $data = $cbr->send(new DailyEng($today));
    foreach ($data['Valute'] ?? [] as $v) {
        if (($v['CharCode'] ?? '') === 'USD') {
            info('USD name (EN): ' . ($v['Name'] ?? '—'));
            break;
        }
    }
} catch (ServiceException $e) {
    fail($e->getMessage());
    $errors++;
}

section('Daily — latest available (no date passed)');

try {
    $data = $cbr->send(new Daily());
    info('Latest date: ' . ($data['@attributes']['Date'] ?? '—'));
} catch (ServiceException $e) {
    fail($e->getMessage());
    $errors++;
}

section('Val — currency code directory');

try {
    $data = $cbr->send(new Val());
    info('Entries : ' . count($data['Item'] ?? []));
} catch (ServiceException $e) {
    fail($e->getMessage());
    $errors++;
}

section('ValFull — full currency directory');

try {
    $data = $cbr->send(new ValFull());
    info('Entries : ' . count($data['Item'] ?? []));
} catch (ServiceException $e) {
    fail($e->getMessage());
    $errors++;
}

section('Dynamic — USD history Jan 2024');

try {
    $data = $cbr->send(new Dynamic($jan1, $jan31, 'R01235')); // R01235 = USD
    $records = $data['Record'] ?? [];
    info('Data points : ' . count($records));
    if (!empty($records)) {
        $first = reset($records);
        $last = end($records);
        info(sprintf('  First: %s → %s RUB', $first['@attributes']['Date'] ?? '?', $first['Value'] ?? '?'));
        info(sprintf('  Last:  %s → %s RUB', $last['@attributes']['Date'] ?? '?', $last['Value'] ?? '?'));
    }
} catch (ServiceException $e) {
    fail($e->getMessage());
    $errors++;
}

section('Metal — precious metals (last 7 days)');

try {
    $data = $cbr->send(new Metal($weekAgo, $today));
    $records = $data['Record'] ?? [];
    info('Records : ' . count($records));
    if (!empty($records)) {
        $r = reset($records);
        info(sprintf('  Gold buy price: %s', $r['Buy'] ?? '—'));
    }
} catch (ServiceException $e) {
    fail($e->getMessage());
    $errors++;
}

section('Ostat — correspondent account balances (last 30 days)');

try {
    $data = $cbr->send(new Ostat($monthAgo, $today));
    info('Records : ' . count($data['OstatDate'] ?? $data));
} catch (ServiceException $e) {
    fail($e->getMessage());
    $errors++;
}

section('Mkr — interbank lending rates (last 30 days)');

try {
    $data = $cbr->send(new Mkr($monthAgo, $today));
    $rows = $data['MKR'] ?? $data;
    info('Records : ' . (is_array($rows) ? count($rows) : 0));
} catch (ServiceException $e) {
    fail($e->getMessage());
    $errors++;
}

section('Depo — deposit rates (last 30 days)');

try {
    $data = $cbr->send(new Depo($monthAgo, $today));
    $rows = $data['Dep'] ?? $data;
    info('Records : ' . (is_array($rows) ? count($rows) : 0));
} catch (ServiceException $e) {
    fail($e->getMessage());
    $errors++;
}

section('Swap — FX-swap rates (last 30 days)');

try {
    $data = $cbr->send(new Swap($monthAgo, $today));
    $rows = $data['Swap'] ?? $data;
    info('Records : ' . (is_array($rows) ? count($rows) : 0));
} catch (ServiceException $e) {
    fail($e->getMessage());
    $errors++;
}

section('Bic — BIC lookup (name search)');

try {
    $data = $cbr->send(new Bic('Сбербанк'));
    $items = $data['BICs']['BIC'] ?? $data;
    info('Matches : ' . (is_array($items) ? count($items) : 0));
} catch (ServiceException $e) {
    fail($e->getMessage());
    $errors++;
}

section('Coins — investment coin prices (last 30 days)');

try {
    $data = $cbr->send(new Coins($monthAgo, $today));
    $items = $data['Gold'] ?? $data['Coins'] ?? $data;
    info('Records : ' . (is_array($items) ? count($items) : 0));
} catch (ServiceException $e) {
    fail($e->getMessage());
    $errors++;
}

$line = str_repeat('─', 60);
echo "\n{$line}\n";
echo ($errors === 0)
    ? "  RESULT: all checks passed.\n"
    : "  RESULT: {$errors} error(s) found.\n";
echo "{$line}\n\n";

exit($errors > 0 ? 1 : 0);
