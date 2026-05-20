<?php

use PHPUnit\Framework\TestCase;

/**
 * Concrete subclass for testing the abstract ServiceApi.
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
                'url' => '/search.asp',
                'params' => ['query', 'limit'],
            ],
        ];
    }

    // Expose protected methods for testing
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
 * Subclass with no URL set — used to test constructor guard.
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
    private ConcreteServiceApi $api;

    protected function setUp(): void
    {
        $_SERVER['HTTP_HOST'] = 'www.example.com';
        $this->api = new ConcreteServiceApi();
    }

    // --- Constructor ---

    public function testConstructorThrowsWhenUrlNotSet(): void
    {
        $this->expectException(CException::class);
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
        $xml = '<?xml version="1.0" encoding="UTF-8"?><root><item>value</item></root>';
        $result = $this->api->parseResponse($xml);
        $this->assertIsArray($result);
    }

    public function testParseResponseMapsSimpleElement(): void
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?><root><name>test</name></root>';
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

    public function testParseResponseHandlesAttributes(): void
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?><root><item id="42">val</item></root>';
        $result = $this->api->parseResponse($xml);
        $this->assertArrayHasKey('item', $result);
    }

    public function testParseResponseHandlesMultipleSiblings(): void
    {
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<root>
    <Valute>
        <NumCode>840</NumCode>
        <CharCode>USD</CharCode>
        <Value>75.00</Value>
    </Valute>
    <Valute>
        <NumCode>978</NumCode>
        <CharCode>EUR</CharCode>
        <Value>85.00</Value>
    </Valute>
</root>
XML;
        $result = $this->api->parseResponse($xml);
        $this->assertArrayHasKey('Valute', $result);
        $this->assertIsArray($result['Valute']);
    }

    // --- getMethodsList ---

    public function testBaseGetMethodsListReturnsArray(): void
    {
        // ConcreteServiceApi overrides this; test the base via a minimal subclass
        $api = new class extends ServiceApi {
            protected $url = 'http://example.com';
            public function getMethodsList(): array { return parent::getMethodsList(); }
        };
        $this->assertSame([], $api->getMethodsList());
    }

    // --- checkParams ---

    public function testCheckParamsFiltersAllowedKeys(): void
    {
        $result = $this->api->publicCheckParams('search', [
            'query' => 'phpunit',
            'limit' => 10,
            'forbidden' => 'should_be_removed',
        ]);

        $this->assertArrayHasKey('query', $result);
        $this->assertArrayHasKey('limit', $result);
        $this->assertArrayNotHasKey('forbidden', $result);
    }

    public function testCheckParamsReturnsAllAllowedKeys(): void
    {
        $result = $this->api->publicCheckParams('search', ['query' => 'test', 'limit' => 5]);
        $this->assertSame(['query' => 'test', 'limit' => 5], $result);
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

    public function testGetHeadersReturnsHttpArray(): void
    {
        $headers = $this->api->publicGetHeaders();
        $this->assertArrayHasKey('http', $headers);
        $this->assertSame('GET', $headers['http']['method']);
    }

    public function testGetHeadersContainsReferer(): void
    {
        $headers = $this->api->publicGetHeaders();
        $this->assertStringContainsString('Referer:', $headers['http']['header']);
    }
}
