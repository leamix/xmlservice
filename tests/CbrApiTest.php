<?php

declare(strict_types=1);

namespace Leamix\XmlService\Tests;

use DateTime;
use DateTimeImmutable;
use Leamix\XmlService\CbrApi;
use Leamix\XmlService\Exception\ServiceException;
use Leamix\XmlService\Http\HttpClientInterface;
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
use PHPUnit\Framework\TestCase;

/**
 * Records the URL of every call and returns configurable XML.
 */
class StubHttpClient implements HttpClientInterface
{
    public string $lastUrl = '';
    public string $response = '<root/>';

    public function get(string $url): string
    {
        $this->lastUrl = $url;
        return $this->response;
    }
}

/**
 * Always throws, for testing error propagation.
 */
class FailingHttpClient implements HttpClientInterface
{
    public function get(string $url): string
    {
        throw new ServiceException("Request to {$url} failed: connection refused");
    }
}

class CbrApiTest extends TestCase
{
    private StubHttpClient $http;
    private CbrApi $api;

    protected function setUp(): void
    {
        $this->http = new StubHttpClient();
        $this->api = new CbrApi($this->http);
    }

    // -------------------------------------------------------------------------
    // send() / sendRaw()
    // -------------------------------------------------------------------------

    public function testSendReturnsParsedArray(): void
    {
        $this->http->response = '<?xml version="1.0"?><root><item>x</item></root>';
        $result = $this->api->send(new Val());
        $this->assertIsArray($result);
        $this->assertSame('x', $result['item']);
    }

    public function testSendRawReturnsRawString(): void
    {
        $this->http->response = '<root/>';
        $this->assertSame('<root/>', $this->api->sendRaw(new Val()));
    }

    public function testSendPropagatesHttpClientException(): void
    {
        $api = new CbrApi(new FailingHttpClient());
        $this->expectException(ServiceException::class);
        $api->send(new Val());
    }

    public function testRequestUrlUsesHttps(): void
    {
        $this->api->send(new Val());
        $this->assertStringStartsWith('https://', $this->http->lastUrl);
    }

    // -------------------------------------------------------------------------
    // URL construction
    // -------------------------------------------------------------------------

    public function testUrlHasNoQueryStringWhenParamsEmpty(): void
    {
        $this->api->send(new Val());
        $this->assertStringNotContainsString('?', $this->http->lastUrl);
    }

    public function testUrlHasQueryStringWhenParamsPresent(): void
    {
        $this->api->send(new Daily(new DateTime('2024-05-01')));
        $this->assertStringContainsString('?', $this->http->lastUrl);
    }

    // -------------------------------------------------------------------------
    // parseResponse
    // -------------------------------------------------------------------------

    public function testParseResponseReturnsArray(): void
    {
        $result = $this->api->parseResponse(
            '<?xml version="1.0" encoding="UTF-8"?><root><item>value</item></root>',
        );
        $this->assertIsArray($result);
    }

    public function testParseResponseMapsSimpleElement(): void
    {
        $result = $this->api->parseResponse(
            '<?xml version="1.0" encoding="UTF-8"?><root><name>test</name></root>',
        );
        $this->assertSame('test', $result['name']);
    }

    public function testParseResponseHandlesNestedElements(): void
    {
        $result = $this->api->parseResponse(
            '<?xml version="1.0"?><root><parent><child>hello</child></parent></root>',
        );
        $this->assertSame('hello', $result['parent']['child']);
    }

    public function testParseResponseCollapsesRepeatedSiblingsIntoArray(): void
    {
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<root>
    <Valute><CharCode>USD</CharCode></Valute>
    <Valute><CharCode>EUR</CharCode></Valute>
</root>
XML;
        $result = $this->api->parseResponse($xml);
        $this->assertIsArray($result['Valute']);
        $this->assertCount(2, $result['Valute']);
    }

    public function testParseResponseThrowsOnInvalidXml(): void
    {
        $this->expectException(ServiceException::class);
        $this->api->parseResponse('not xml at all');
    }

    public function testParseResponseRestoresLibxmlErrorState(): void
    {
        $prev = libxml_use_internal_errors(false);

        try {
            $this->api->parseResponse('<?xml version="1.0"?><root><item>x</item></root>');
        } finally {
            $restored = libxml_use_internal_errors($prev);
        }

        $this->assertFalse($restored);
    }

    // -------------------------------------------------------------------------
    // Daily
    // -------------------------------------------------------------------------

    public function testDailyFormatsDate(): void
    {
        $this->api->send(new Daily(new DateTime('2024-05-01')));
        $this->assertStringContainsString('date_req=01%2F05%2F2024', $this->http->lastUrl);
    }

    public function testDailyAcceptsDateTimeImmutable(): void
    {
        $this->api->send(new Daily(new DateTimeImmutable('2024-05-01')));
        $this->assertStringContainsString('date_req=01%2F05%2F2024', $this->http->lastUrl);
    }

    public function testDailyOmitsDateParamWhenNull(): void
    {
        $this->api->send(new Daily());
        $this->assertStringNotContainsString('date_req', $this->http->lastUrl);
    }

    // -------------------------------------------------------------------------
    // DailyEng
    // -------------------------------------------------------------------------

    public function testDailyEngFormatsDate(): void
    {
        $this->api->send(new DailyEng(new DateTime('2024-01-31')));
        $this->assertStringContainsString('date_req=31%2F01%2F2024', $this->http->lastUrl);
    }

    public function testDailyEngOmitsDateParamWhenNull(): void
    {
        $this->api->send(new DailyEng());
        $this->assertStringNotContainsString('date_req', $this->http->lastUrl);
    }

    // -------------------------------------------------------------------------
    // Dynamic
    // -------------------------------------------------------------------------

    public function testDynamicFormatsAllParams(): void
    {
        $this->api->send(new Dynamic(new DateTime('2024-01-01'), new DateTime('2024-01-31'), 'R01235'));
        $url = $this->http->lastUrl;
        $this->assertStringContainsString('date_req1=01%2F01%2F2024', $url);
        $this->assertStringContainsString('date_req2=31%2F01%2F2024', $url);
        $this->assertStringContainsString('VAL_NM_RQ=R01235', $url);
    }

    // -------------------------------------------------------------------------
    // Date-range requests — shared param format
    // -------------------------------------------------------------------------

    /**
     * @dataProvider dateRangeProvider
     */
    public function testDateRangeRequestFormatsParams(object $request): void
    {
        $this->api->send($request);
        $url = $this->http->lastUrl;
        $this->assertStringContainsString('date_req1=01%2F01%2F2024', $url);
        $this->assertStringContainsString('date_req2=31%2F01%2F2024', $url);
    }

    public static function dateRangeProvider(): array
    {
        $d1 = new DateTime('2024-01-01');
        $d2 = new DateTime('2024-01-31');

        return [
            'Metall' => [new Metal($d1, $d2)],
            'Ostat' => [new Ostat($d1, $d2)],
            'Mkr' => [new Mkr($d1, $d2)],
            'Depo' => [new Depo($d1, $d2)],
            'Swap' => [new Swap($d1, $d2)],
            'Coins' => [new Coins($d1, $d2)],
        ];
    }

    // -------------------------------------------------------------------------
    // No-param requests
    // -------------------------------------------------------------------------

    /**
     * @dataProvider noParamProvider
     */
    public function testNoParamRequestSendsNoQueryString(object $request): void
    {
        $this->api->send($request);
        $this->assertStringNotContainsString('?', $this->http->lastUrl);
    }

    public static function noParamProvider(): array
    {
        return [
            'Val' => [new Val()],
            'ValFull' => [new ValFull()],
        ];
    }

    // -------------------------------------------------------------------------
    // Bic
    // -------------------------------------------------------------------------

    public function testBicSendsNoParamsWhenBothEmpty(): void
    {
        $this->api->send(new Bic());
        $this->assertStringNotContainsString('?', $this->http->lastUrl);
    }

    public function testBicPassesNameOnlyWhenBicIsEmpty(): void
    {
        $this->api->send(new Bic('Сбербанк'));
        $url = $this->http->lastUrl;
        $this->assertStringContainsString('name=', $url);
        $this->assertStringNotContainsString('bic=', $url);
    }

    public function testBicPassesBicOnlyWhenNameIsEmpty(): void
    {
        $this->api->send(new Bic('', '044525225'));
        $url = $this->http->lastUrl;
        $this->assertStringContainsString('bic=044525225', $url);
        $this->assertStringNotContainsString('name=', $url);
    }

    public function testBicPassesBothParamsWhenProvided(): void
    {
        $this->api->send(new Bic('Сбербанк', '044525225'));
        $url = $this->http->lastUrl;
        $this->assertStringContainsString('name=', $url);
        $this->assertStringContainsString('bic=044525225', $url);
    }
}
