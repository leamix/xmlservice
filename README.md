# xmlservice

Framework-agnostic PHP library for the [Central Bank of Russia (CBR) XML API](https://www.cbr.ru/development/SXML/).

Each API endpoint is a standalone request class. Pass it to `CbrApi::send()` to get a parsed array, or `sendRaw()` for
the raw XML string. Swap the HTTP transport at construction time — no subclassing needed.

---

## Requirements

- PHP >= 7.4
- Composer

---

## Installation

```bash
composer require leamix/xmlservice
```

---

## Quick start

```php
use Leamix\XmlService\CbrApi;
use Leamix\XmlService\Exception\ServiceException;
use Leamix\XmlService\Request\Daily;
use Leamix\XmlService\Request\Dynamic;
use Leamix\XmlService\Request\Ostat;

$cbr = new CbrApi();

try {
    // Today's exchange rates → parsed array
    $rates = $cbr->send(new Daily(new DateTimeImmutable('today')));
    foreach ($rates['Valute'] as $v) {
        if ($v['CharCode'] === 'USD') {
            echo 'USD: ' . $v['Value'] . ' RUB';
        }
    }

    // Latest rates (no date) → raw XML string
    $xml = $cbr->sendRaw(new Daily());

    // Historical USD rates for January 2024
    $history = $cbr->send(new Dynamic(
        new DateTimeImmutable('2024-01-01'),
        new DateTimeImmutable('2024-01-31'),
        'R01235' // currency code from Val request
    ));

    // Correspondent account balances for the last 30 days
    $ostat = $cbr->send(new Ostat(
        new DateTimeImmutable('-30 days'),
        new DateTimeImmutable('today')
    ));

} catch (ServiceException $e) {
    echo 'Error: ' . $e->getMessage();
}
```

---

## Request classes

| Class      | Description                    | Constructor                                       |
|------------|--------------------------------|---------------------------------------------------|
| `Daily`    | Daily official rates           | `(?DateTimeInterface $date)`                      |
| `DailyEng` | Daily rates, English           | `(?DateTimeInterface $date)`                      |
| `Dynamic`  | Rate history for one currency  | `(DateTimeInterface $from, $to, string $valNmRq)` |
| `Val`      | Currency code directory        | —                                                 |
| `ValFull`  | Full currency code directory   | —                                                 |
| `Metall`   | Precious metal prices          | `(DateTimeInterface $from, $to)`                  |
| `Ostat`    | Correspondent account balances | `(DateTimeInterface $from, $to)`                  |
| `Mkr`      | Interbank lending rates        | `(DateTimeInterface $from, $to)`                  |
| `Depo`     | Deposit operation rates        | `(DateTimeInterface $from, $to)`                  |
| `Swap`     | FX-swap overnight rates        | `(DateTimeInterface $from, $to)`                  |
| `Bic`      | BIC ↔ bank name lookup         | `(string $name = '', string $bic = '')`           |
| `Coins`    | Investment coin prices         | `(DateTimeInterface $from, $to)`                  |

---

## Custom HTTP transport

`CbrApi` accepts any `HttpClientInterface` in its constructor. The interface has a single method:

```php
public function get(string $url): string; // throws ServiceException on failure
```

The built-in `DefaultHttpClient` uses `file_get_contents` with HTTPS, a configurable timeout, and HTTP status code
checking. Pass a custom implementation to add caching, retries, logging, or to use Guzzle / Symfony HttpClient:

### Guzzle adapter

```php
use GuzzleHttp\Client;
use Leamix\XmlService\CbrApi;
use Leamix\XmlService\Exception\ServiceException;
use Leamix\XmlService\Http\HttpClientInterface;

class GuzzleAdapter implements HttpClientInterface
{
    public function __construct(private Client $client) {}

    public function get(string $url): string
    {
        try {
            return (string) $this->client->get($url)->getBody();
        } catch (\Throwable $e) {
            throw new ServiceException("Request to {$url} failed: " . $e->getMessage(), 0, $e);
        }
    }
}

$cbr = new CbrApi(new GuzzleAdapter(new Client(['timeout' => 30])));
```

### Symfony HttpClient adapter

```php
use Leamix\XmlService\CbrApi;
use Leamix\XmlService\Exception\ServiceException;
use Leamix\XmlService\Http\HttpClientInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface as SymfonyClient;

class SymfonyAdapter implements HttpClientInterface
{
    public function __construct(private SymfonyClient $client) {}

    public function get(string $url): string
    {
        try {
            return $this->client->request('GET', $url)->getContent();
        } catch (\Throwable $e) {
            throw new ServiceException("Request to {$url} failed: " . $e->getMessage(), 0, $e);
        }
    }
}

$cbr = new CbrApi(new SymfonyAdapter(\Symfony\Component\HttpClient\HttpClient::create()));
```

### In-memory stub for tests

```php
use Leamix\XmlService\CbrApi;
use Leamix\XmlService\Http\HttpClientInterface;
use Leamix\XmlService\Request\Daily;

class StubHttpClient implements HttpClientInterface
{
    public function get(string $url): string
    {
        return file_get_contents(__DIR__ . '/fixtures/daily.xml');
    }
}

$cbr  = new CbrApi(new StubHttpClient());
$data = $cbr->send(new Daily());
```

---

## Custom request class

Implement `RequestInterface` to wrap any endpoint not covered by the bundled classes:

```php
use Leamix\XmlService\Request\RequestInterface;

class MyEndpoint implements RequestInterface
{
    public function getEndpoint(): string { return 'XML_custom.asp'; }
    public function getParams(): array    { return ['param' => 'value']; }
}

$data = $cbr->send(new MyEndpoint());
```

---

## Project structure

```
src/
├── Http/
│   ├── HttpClientInterface.php   # HTTP transport contract
│   └── DefaultHttpClient.php     # file_get_contents implementation
├── Request/
│   ├── RequestInterface.php      # endpoint + params contract
│   ├── FormatsDate.php           # date formatting trait
│   ├── Daily.php
│   ├── DailyEng.php
│   ├── Dynamic.php
│   ├── Val.php
│   ├── ValFull.php
│   ├── Metall.php
│   ├── Ostat.php
│   ├── Mkr.php
│   ├── Depo.php
│   ├── Swap.php
│   ├── Bic.php
│   └── Coins.php
├── Exception/
│   └── ServiceException.php      # RuntimeException for all library errors
└── CbrApi.php                    # orchestrator

tests/
├── bootstrap.php
└── CbrApiTest.php
```

---

## Running tests

```bash
composer install
./vendor/bin/phpunit --testdox
```

---

## Smoke test

Run real requests against cbr.ru:

```bash
php examples/smoke.php
```

## Usage example

```bash
php examples/example.php
```
