<?php

declare(strict_types=1);

/**
 * Smoke test / usage demo for the xmlservice library.
 *
 * Run: php example.php
 */

require_once __DIR__ . '/vendor/autoload.php';

use Leamix\XmlService\CbrApi;
use Leamix\XmlService\Exception\ServiceException;

// ─────────────────────────────────────────────
// Output helpers
// ─────────────────────────────────────────────

function section(string $title): void
{
    $line = str_repeat('─', 60);
    echo "\n{$line}\n  {$title}\n{$line}\n";
}

function ok(string $msg): void   { echo "  [OK]  {$msg}\n"; }
function fail(string $msg): void { echo "  [FAIL] {$msg}\n"; }
function info(string $msg): void { echo "  {$msg}\n"; }

$errors = 0;

// ─────────────────────────────────────────────
// 1. CbrApi — today's currency rates
// ─────────────────────────────────────────────

section('CbrApi :: daily() — currency rates for today');

try {
    $cbr  = new CbrApi();
    $date = date('d/m/Y');
    $data = $cbr->daily(['date_req' => $date]);

    ok('Request sent: ' . $cbr->getLatestUrl());
    info('Rate date      : ' . ($data['@attributes']['Date'] ?? $date));
    info('Currencies     : ' . count($data['Valute'] ?? []));

    foreach (['USD', 'EUR'] as $code) {
        $found = false;
        foreach ($data['Valute'] ?? [] as $v) {
            if (($v['CharCode'] ?? '') === $code) {
                info(sprintf('  %-4s  %s RUB (lot %s)', $code, $v['Value'], $v['Nominal']));
                $found = true;
                break;
            }
        }
        if (!$found) {
            info("  {$code}: not found in response");
        }
    }
} catch (ServiceException $e) {
    fail('CbrApi::daily() — ' . $e->getMessage());
    $errors++;
}

// ─────────────────────────────────────────────
// 2. CbrApi — raw XML response
// ─────────────────────────────────────────────

section('CbrApi :: daily() — raw XML response');

try {
    $cbr = new CbrApi();
    $xml = $cbr->daily(['date_req' => date('d/m/Y')], false);

    if (is_string($xml) && strpos($xml, '<?xml') !== false) {
        ok('Received XML (' . strlen($xml) . ' bytes)');
        info(substr($xml, 0, 200) . ' …');
    } else {
        fail('Expected XML string, got: ' . gettype($xml));
        $errors++;
    }
} catch (ServiceException $e) {
    fail('CbrApi::daily(parse=false) — ' . $e->getMessage());
    $errors++;
}

// ─────────────────────────────────────────────
// 3. CbrApi — method registry
// ─────────────────────────────────────────────

section('CbrApi :: getMethodsList()');

$cbr   = new CbrApi();
$count = count($cbr->getMethodsList());

if ($count === 11) {
    ok("Registered {$count} methods: " . implode(', ', array_keys($cbr->getMethodsList())));
} else {
    fail("Expected 11 methods, found: {$count}");
    $errors++;
}

// ─────────────────────────────────────────────
// Result
// ─────────────────────────────────────────────

$line = str_repeat('─', 60);
echo "\n{$line}\n";
echo ($errors === 0)
    ? "  RESULT: all checks passed.\n"
    : "  RESULT: {$errors} critical error(s) found.\n";
echo "{$line}\n\n";

exit($errors > 0 ? 1 : 0);
