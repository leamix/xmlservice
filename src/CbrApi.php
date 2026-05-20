<?php

declare(strict_types=1);

namespace Leamix\XmlService;

use Leamix\XmlService\Exception\ServiceException;

/**
 * Adapter for the Central Bank of Russia XML service (cbr.ru/scripts).
 *
 * Provides access to currency rates, interbank rates, BIC lookups,
 * deposit operations, investment coins, and more.
 *
 * Usage:
 *   $cbr  = new CbrApi();
 *   $data = $cbr->daily(['date_req' => date('d/m/Y')]);
 *   echo $data['Valute'][9]['Value']; // USD rate
 *
 * @see http://www.cbr.ru/scripts/Root.asp?Prtid=SXML
 *
 * @method array|string daily(array $params = [], bool $parse = true)
 * @method array|string daily_eng(array $params = [], bool $parse = true)
 * @method array|string dynamic(array $params = [], bool $parse = true)
 * @method array|string ostat(array $params = [], bool $parse = true)
 * @method array|string mkr(array $params = [], bool $parse = true)
 * @method array|string depo(array $params = [], bool $parse = true)
 * @method array|string search(array $params = [], bool $parse = true)
 * @method array|string news(array $params = [], bool $parse = true)
 * @method array|string bic(array $params = [], bool $parse = true)
 * @method array|string swap(array $params = [], bool $parse = true)
 * @method array|string coins(array $params = [], bool $parse = true)
 */
class CbrApi extends ServiceApi
{
    protected string $url = 'http://www.cbr.ru/scripts/';

    /**
     * @param string $name
     * @param array $parameters [0 => params array, 1 => bool parse]
     * @return array|string
     * @throws ServiceException on unknown method or connection failure
     */
    public function __call(string $name, array $parameters)
    {
        $methods = $this->getMethodsList();

        if (!array_key_exists($name, $methods)) {
            throw new ServiceException(
                static::class . ' does not have a method named "' . $name . '".',
            );
        }

        $url = $this->url . $methods[$name]['url'];
        $params = $this->checkParams($name, $parameters[0] ?? []);
        $parse = isset($parameters[1]) ? (bool)$parameters[1] : true;

        return $this->response($params, $url, $parse);
    }

    public function getMethodsList(): array
    {
        return [
            // Daily currency rates for a given date
            'daily' => [
                'url' => 'XML_daily.asp',
                'params' => ['date_req'],
            ],

            // Daily currency rates (English version)
            'daily_eng' => [
                'url' => 'XML_daily_eng.asp',
                'params' => ['date_req'],
            ],

            // Historical rate series for a currency in a date range
            'dynamic' => [
                'url' => 'XML_dynamic.asp',
                'params' => ['date_req1', 'date_req2', 'VAL_NM_RQ'],
            ],

            // Correspondent account balances of credit institutions
            'ostat' => [
                'url' => 'XML_ostat.asp',
                'params' => ['date_req1', 'date_req2'],
            ],

            // Interbank lending market rates
            'mkr' => [
                'url' => 'xml_mkr.asp',
                'params' => ['date_req1', 'date_req2'],
            ],

            // Deposit operation rates
            'depo' => [
                'url' => 'xml_depo.asp',
                'params' => ['date_req1', 'date_req2'],
            ],

            // Full-text search on cbr.ru
            'search' => [
                'url' => 'XML_search.asp',
                'params' => ['SearchString'],
            ],

            // Latest news from cbr.ru
            'news' => [
                'url' => 'XML_News.asp',
                'params' => [],
            ],

            // BIC ↔ bank name lookup
            'bic' => [
                'url' => 'XML_bic.asp',
                'params' => ['name', 'bic'],
            ],

            // Overnight FX-swap rates
            'swap' => [
                'url' => 'xml_swap.asp',
                'params' => ['date_req1', 'date_req2'],
            ],

            // CBR investment coin prices
            'coins' => [
                'url' => 'XMLCoinsBase.asp',
                'params' => ['date_req1', 'date_req2'],
            ],
        ];
    }
}
