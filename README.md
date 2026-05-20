# xmlservice

Framework-agnostic PHP library for consuming XML web services through a shared base class. Ships with a ready-made adapter for the Central Bank of Russia (CBR) XML API.

---

## Classes

| Class | Description |
|---|---|
| `Leamix\XmlService\ServiceApi` | Abstract base. Sends HTTP GET requests and parses XML responses into associative arrays. |
| `Leamix\XmlService\CbrApi` | CBR adapter: currency rates, interbank rates, BIC lookup, deposit operations, investment coins, and more. |
| `Leamix\XmlService\Exception\ServiceException` | Thrown on connection failures and malformed XML. |

---

## Requirements

- PHP >= 7.4
- Composer

---

## Installation

```bash
git clone https://github.com/leamix/xmlservice.git
cd xmlservice
composer install
```

Copy the `src/` directory into your project and register the namespace with Composer's PSR-4 autoloader.

---

## Usage

### CbrApi — currency rates

```php
use Leamix\XmlService\CbrApi;
use Leamix\XmlService\Exception\ServiceException;

$cbr = new CbrApi();

try {
    // Today's rates (parsed array)
    $data = $cbr->daily(['date_req' => date('d/m/Y')]);
    echo 'USD: ' . $data['Valute'][9]['Value'];

    // Historical rate series for USD
    $data = $cbr->dynamic([
        'date_req1' => '01/01/2024',
        'date_req2' => '31/01/2024',
        'VAL_NM_RQ' => 'R01235',
    ]);

    // Full-text search on cbr.ru
    $data = $cbr->search(['SearchString' => 'key rate']);

    // Raw XML without parsing
    $xml = $cbr->daily(['date_req' => date('d/m/Y')], false);

} catch (ServiceException $e) {
    echo 'Error: ' . $e->getMessage();
}
```

#### Available methods

| Method | Description | Parameters |
|---|---|---|
| `daily` | Currency rates for a date | `date_req` |
| `daily_eng` | Currency rates, English | `date_req` |
| `dynamic` | Rate history for a currency | `date_req1`, `date_req2`, `VAL_NM_RQ` |
| `ostat` | Correspondent account balances | `date_req1`, `date_req2` |
| `mkr` | Interbank lending rates | `date_req1`, `date_req2` |
| `depo` | Deposit operation rates | `date_req1`, `date_req2` |
| `search` | Full-text search | `SearchString` |
| `news` | Latest news | — |
| `bic` | BIC ↔ bank name lookup | `name`, `bic` |
| `swap` | FX-swap overnight rates | `date_req1`, `date_req2` |
| `coins` | Investment coin prices | `date_req1`, `date_req2` |

---

## Extending

To wrap a new XML service, subclass `ServiceApi` and declare `$url` and `getMethodsList()`:

```php
namespace MyApp;

use Leamix\XmlService\ServiceApi;

class MyApi extends ServiceApi
{
    protected string $url = 'https://api.example.com/';

    public function getMethodsList(): array
    {
        return [
            'getData' => [
                'url'    => 'data.xml',
                'params' => ['from', 'to'],
            ],
        ];
    }
}
```

---

## Project structure

```
src/
├── Exception/
│   └── ServiceException.php   # RuntimeException subclass for library errors
├── ServiceApi.php              # Abstract base class
└── CbrApi.php                  # CBR adapter

tests/
├── bootstrap.php               # Loads the Composer autoloader
├── ServiceApiTest.php          # Base class tests
└── CbrApiTest.php              # CBR adapter tests
```

---

## Smoke test

Run real requests against cbr.ru and print a human-readable report:

```bash
php example.php
```

Sample output:

```
────────────────────────────────────────────────────────────
  CbrApi :: daily() — currency rates for today
────────────────────────────────────────────────────────────
  [OK]  Request sent: http://www.cbr.ru/scripts/XML_daily.asp?date_req=20%2F05%2F2026
  Rate date      : 20.05.2026
  Currencies     : 54
    USD   71.2926 RUB (lot 1)
    EUR   82.7871 RUB (lot 1)
...
  RESULT: all checks passed.
```

---

## Running tests

### Install dependencies

```bash
composer install
```

### Run the suite

```bash
./vendor/bin/phpunit
```

### Verbose output

```bash
./vendor/bin/phpunit --testdox
```

Sample output:

```
CbrApi
 ✔ Instantiates successfully
 ✔ Methods list contains all expected keys
 ✔ Every method entry has url and params keys
 ✔ Daily accepts date req
 ...

ServiceApi
 ✔ Constructor throws when url not set
 ✔ Parse response maps simple element
 ✔ Parse response throws on invalid xml
 ...

OK (31 tests, 93 assertions)
```

---

## Author

Alexander Levin — [x8p@leamix.com](mailto:x8p@leamix.com)
