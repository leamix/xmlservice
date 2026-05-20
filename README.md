# xmlservice

PHP-библиотека для работы с XML-сервисами через единый базовый класс. Включает готовые адаптеры для Центробанка РФ и сервиса геолокации по IP.

---

## Классы

| Класс | Описание |
|---|---|
| `ServiceApi` | Абстрактный базовый класс. Выполняет HTTP-запросы и парсит XML-ответы в ассоциативный массив. |
| `CbrApi` | Доступ к XML-сервису ЦБ РФ (cbr.ru): курсы валют, ставки МБК, депозиты, монеты и др. |
| `IpGeobaseApi` | Определение города, региона и координат по IP-адресу через ipgeobase.ru. |

---

## Требования

- PHP 7.4+
- Yii Framework 1.x (в окружении приложения)
- Для запуска тестов: Composer

---

## Установка

```bash
git clone https://github.com/leamix/xmlservice.git
cd xmlservice
composer install
```

Скопируйте файлы классов в папку `protected/components/` (или в любую другую директорию, подключённую автозагрузкой Yii).

---

## Использование

### CbrApi — курсы валют

```php
$cbr = new CbrApi;

// Курс на сегодня
$data = $cbr->daily(['date_req' => date('d/m/Y')]);
echo 'USD: ' . $data['Valute'][9]['Value'];

// Динамика курса доллара за период
$data = $cbr->dynamic([
    'date_req1' => '01/01/2024',
    'date_req2' => '31/01/2024',
    'VAL_NM_RQ' => 'R01235',
]);

// Поиск по сайту ЦБ
$data = $cbr->search(['SearchString' => 'ключевая ставка']);

// Получить сырой XML без парсинга
$xml = $cbr->daily(['date_req' => date('d/m/Y')], false);
```

#### Доступные методы CbrApi

| Метод | Описание | Параметры |
|---|---|---|
| `daily` | Курсы валют на дату | `date_req` |
| `daily_eng` | Курсы валют (англ.) | `date_req` |
| `dynamic` | Динамика курса валюты | `date_req1`, `date_req2`, `VAL_NM_RQ` |
| `ostat` | Остатки на корсчетах | `date_req1`, `date_req2` |
| `mkr` | Ставки МБК | `date_req1`, `date_req2` |
| `depo` | Депозитные операции | `date_req1`, `date_req2` |
| `search` | Поиск | `SearchString` |
| `news` | Новости | — |
| `bic` | БИК кредитных организаций | `name`, `bic` |
| `swap` | Ставки валютного свопа | `date_req1`, `date_req2` |
| `coins` | Цены на инвестмонеты | `date_req1`, `date_req2` |

---

## Расширение

Для подключения нового XML-сервиса достаточно унаследоваться от `ServiceApi`:

```php
class MyApi extends ServiceApi
{
    protected $url = 'http://api.example.com/';

    public function getMethodsList()
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

## Проверка работоспособности

Скрипт `example.php` выполняет реальные запросы к API и выводит результат в консоль:

```bash
php example.php
```

## Запуск тестов

### 1. Установить зависимости

```bash
composer install
```

### 2. Запустить тесты

```bash
./vendor/bin/phpunit
```

### 3. С подробным выводом

```bash
./vendor/bin/phpunit --testdox
```

### Структура тестов

```
tests/
├── bootstrap.php          # Подключение стабов и исходных классов
├── stubs/
│   └── YiiStubs.php       # Заглушки для CComponent, CException, Yii
├── ServiceApiTest.php     # Тесты базового класса
├── CbrApiTest.php         # Тесты адаптера ЦБ РФ
```

Стабы позволяют запускать тесты без установленного Yii Framework. Тесты, которые зависят от реальных HTTP-запросов к внешним сервисам, перехватывают сетевой вызов через подкласс с переопределённым методом `response()`.
