<?php

declare(strict_types=1);

namespace Leamix\XmlService\Tests;

use Leamix\XmlService\Exception\ServiceException;
use Leamix\XmlService\ServiceApi;
use PHPUnit\Framework\TestCase;

/**
 * Concrete subclass used to exercise the abstract ServiceApi.
 */
class ConcreteServiceApi extends ServiceApi
{
    public function __construct(string $url = 'http://example.com')
    {
        $this->url = $url;
        parent::__construct();
    }

    public function getMethodsList(): array
    {
        return [
            'search' => [
                'url'    => '/search.asp',
                'params' => ['query', 'limit'],
            ],
        ];
    }

    public function publicCheckParams(string $name, array $array): array
    {
        return $this->checkParams($name, $array);
    }

    public function publicGetHeaders(): array
    {
        return $this->getHeaders();
    }
}

/**
 * Subclass with no URL — used to test the constructor guard.
 */
class NoUrlServiceApi extends ServiceApi
{
    public function getMethodsList(): array
    {
        return [];
    }
}

class ServiceApiTest extends TestCase
{
    /** @var ConcreteServiceApi */
    private $api;

    protected function setUp(): void
    {
        $this->api = new ConcreteServiceApi();
    }

    // --- Constructor ---

    public function testConstructorThrowsWhenUrlNotSet(): void
    {
        $this->expectException(ServiceException::class);
        new NoUrlServiceApi();
    }

    public function testConstructorSucceedsWhenUrlIsSet(): void
    {
        $api = new ConcreteServiceApi('http://test.example.com');
        $this->assertInstanceOf(ServiceApi::class, $api);
    }

    // --- getLatestUrl ---

    public function testGetLatestUrlIsNullInitially(): void
    {
        $this->assertNull($this->api->getLatestUrl());
    }

    // --- parseResponse ---

    public function testParseResponseReturnsArray(): void
    {
        $xml    = '<?xml version="1.0" encoding="UTF-8"?><root><item>value</item></root>';
        $result = $this->api->parseResponse($xml);
        $this->assertIsArray($result);
    }

    public function testParseResponseMapsSimpleElement(): void
    {
        $xml    = '<?xml version="1.0" encoding="UTF-8"?><root><name>test</name></root>';
        $result = $this->api->parseResponse($xml);
        $this->assertSame('test', $result['name']);
    }

    public function testParseResponseHandlesNestedElements(): void
    {
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<root>
    <parent>
        <child>hello</child>
    </parent>
</root>
XML;
        $result = $this->api->parseResponse($xml);
        $this->assertSame('hello', $result['parent']['child']);
    }

    public function testParseResponseHandlesMultipleSiblings(): void
    {
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<root>
    <Valute><CharCode>USD</CharCode><Value>75.00</Value></Valute>
    <Valute><CharCode>EUR</CharCode><Value>85.00</Value></Valute>
</root>
XML;
        $result = $this->api->parseResponse($xml);
        $this->assertArrayHasKey('Valute', $result);
        $this->assertIsArray($result['Valute']);
    }

    public function testParseResponseThrowsOnInvalidXml(): void
    {
        $this->expectException(ServiceException::class);
        $this->api->parseResponse('this is not xml');
    }

    // --- getMethodsList ---

    public function testBaseGetMethodsListReturnsEmptyArray(): void
    {
        $api = new class extends ServiceApi {
            protected string $url = 'http://example.com';

            public function getMethodsList(): array
            {
                return parent::getMethodsList();
            }
        };
        $this->assertSame([], $api->getMethodsList());
    }

    // --- checkParams ---

    public function testCheckParamsFiltersAllowedKeys(): void
    {
        $result = $this->api->publicCheckParams('search', [
            'query'     => 'phpunit',
            'limit'     => 10,
            'forbidden' => 'should_be_removed',
        ]);

        $this->assertArrayHasKey('query', $result);
        $this->assertArrayHasKey('limit', $result);
        $this->assertArrayNotHasKey('forbidden', $result);
    }

    public function testCheckParamsPreservesValues(): void
    {
        $result = $this->api->publicCheckParams('search', ['query' => 'test', 'limit' => 5]);
        $this->assertSame(['query' => 'test', 'limit' => 5], $result);
    }

    public function testCheckParamsReturnsEmptyArrayForNonexistentMethod(): void
    {
        $result = $this->api->publicCheckParams('nonexistent', ['query' => 'test']);
        $this->assertSame([], $result);
    }

    public function testCheckParamsReturnsEmptyArrayForEmptyInput(): void
    {
        $result = $this->api->publicCheckParams('search', []);
        $this->assertSame([], $result);
    }

    public function testCheckParamsReturnsEmptyArrayForEmptyName(): void
    {
        $result = $this->api->publicCheckParams('', ['query' => 'test']);
        $this->assertSame([], $result);
    }

    // --- getHeaders ---

    public function testGetHeadersReturnsHttpMethodGet(): void
    {
        $headers = $this->api->publicGetHeaders();
        $this->assertArrayHasKey('http', $headers);
        $this->assertSame('GET', $headers['http']['method']);
    }
}
