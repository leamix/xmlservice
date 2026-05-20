<?php

require_once __DIR__ . '/tests/stubs/YiiStubs.php';
require_once __DIR__ . '/ServiceApi.php';
require_once __DIR__ . '/CbrApi.php';
require_once __DIR__ . '/IpGeobaseApi.php';

$_SERVER['HTTP_HOST'] = 'example.com';

function header_line(string $title): void
{
    $line = str_repeat('─', 60);
    echo "\n{$line}\n  {$title}\n{$line}\n";
}

function ok(string $msg): void   { echo "  [OK]  {$msg}\n"; }
function fail(string $msg): void { echo "  [FAIL] {$msg}\n"; }
function info(string $msg): void { echo "  {$msg}\n"; }

$errors = 0;

// ─────────────────────────────────────────────
// 1. CbrApi — курс валют на сегодня
// ─────────────────────────────────────────────

header_line('CbrApi :: daily() — курс валют на сегодня');

try {
    $cbr  = new CbrApi();
    $date = date('d/m/Y');
    $data = $cbr->daily(['date_req' => $date]);

    ok("Запрос выполнен: " . $cbr->getLatestUrl());
    info("Дата котировки : " . ($data['@attributes']['Date'] ?? $date));
    info("Количество валют: " . count($data['Valute'] ?? []));

    // Найдём USD и EUR
    foreach (['USD', 'EUR'] as $code) {
        $found = false;
        foreach ($data['Valute'] ?? [] as $v) {
            if (($v['CharCode'] ?? '') === $code) {
                info(sprintf('  %-4s  %s руб. (номинал %s)', $code, $v['Value'], $v['Nominal']));
                $found = true;
                break;
            }
        }
        if (!$found) {
            info("  {$code}: не найден в ответе");
        }
    }
} catch (CException $e) {
    fail("CbrApi::daily() — " . $e->getMessage());
    $errors++;
}

// ─────────────────────────────────────────────
// 2. CbrApi — сырой XML (без парсинга)
// ─────────────────────────────────────────────

header_line('CbrApi :: daily() — сырой XML-ответ');

try {
    $cbr = new CbrApi();
    $xml = $cbr->daily(['date_req' => date('d/m/Y')], false);

    if (is_string($xml) && str_contains($xml, '<?xml')) {
        ok("Получен XML (" . strlen($xml) . " байт)");
        info(substr($xml, 0, 200) . ' …');
    } else {
        fail("Ожидался XML-string, получено: " . gettype($xml));
        $errors++;
    }
} catch (CException $e) {
    fail("CbrApi::daily(parse=false) — " . $e->getMessage());
    $errors++;
}

// ─────────────────────────────────────────────
// 3. CbrApi — список методов
// ─────────────────────────────────────────────

header_line('CbrApi :: getMethodsList()');

$cbr     = new CbrApi();
$methods = $cbr->getMethodsList();
$count   = count($methods);

if ($count === 11) {
    ok("Зарегистрировано {$count} методов: " . implode(', ', array_keys($methods)));
} else {
    fail("Ожидалось 11 методов, найдено: {$count}");
    $errors++;
}

// ─────────────────────────────────────────────
// Итог
// ─────────────────────────────────────────────

$line = str_repeat('─', 60);
echo "\n{$line}\n";

if ($errors === 0) {
    echo "  РЕЗУЛЬТАТ: все проверки прошли успешно.\n";
} else {
    echo "  РЕЗУЛЬТАТ: обнаружено критических ошибок — {$errors}.\n";
}

echo "{$line}\n\n";

exit($errors > 0 ? 1 : 0);
