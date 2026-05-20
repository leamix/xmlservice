<?php

use PHPUnit\Framework\TestCase;

class CbrApiTest extends TestCase
{
    private CbrApi $api;

    protected function setUp(): void
    {
        $_SERVER['HTTP_HOST'] = 'www.example.com';
        $this->api = new CbrApi();
    }

    // --- Instantiation ---

    public function testInstantiatesSuccessfully(): void
    {
        $this->assertInstanceOf(CbrApi::class, $this->api);
    }

    // --- getMethodsList ---

    public function testMethodsListContainsAllExpectedMethods(): void
    {
        $methods = $this->api->getMethodsList();
        $expected = ['daily', 'daily_eng', 'dynamic', 'ostat', 'mkr', 'depo', 'search', 'news', 'bic', 'swap', 'coins'];

        foreach ($expected as $name) {
            $this->assertArrayHasKey($name, $methods, "Method '{$name}' not found in methodsList");
        }
    }

    public function testMethodsListHasExactlyElevenMethods(): void
    {
        $this->assertCount(11, $this->api->getMethodsList());
    }

    public function testEachMethodEntryHasUrlAndParams(): void
    {
        foreach ($this->api->getMethodsList() as $name => $entry) {
            $this->assertArrayHasKey('url', $entry, "Method '{$name}' missing 'url'");
            $this->assertArrayHasKey('params', $entry, "Method '{$name}' missing 'params'");
            $this->assertIsString($entry['url'], "Method '{$name}' url must be a string");
            $this->assertIsArray($entry['params'], "Method '{$name}' params must be an array");
        }
    }

    // --- Method-specific params ---

    public function testDailyAcceptsDateReqParam(): void
    {
        $methods = $this->api->getMethodsList();
        $this->assertContains('date_req', $methods['daily']['params']);
    }

    public function testDailyEngAcceptsDateReqParam(): void
    {
        $methods = $this->api->getMethodsList();
        $this->assertContains('date_req', $methods['daily_eng']['params']);
    }

    public function testDynamicAcceptsDateRangeAndCurrencyCode(): void
    {
        $methods = $this->api->getMethodsList();
        $params = $methods['dynamic']['params'];
        $this->assertContains('date_req1', $params);
        $this->assertContains('date_req2', $params);
        $this->assertContains('VAL_NM_RQ', $params);
    }

    public function testOstatAcceptsDateRange(): void
    {
        $methods = $this->api->getMethodsList();
        $params = $methods['ostat']['params'];
        $this->assertContains('date_req1', $params);
        $this->assertContains('date_req2', $params);
    }

    public function testNewsHasNoRequiredParams(): void
    {
        $methods = $this->api->getMethodsList();
        $this->assertSame([], $methods['news']['params']);
    }

    public function testBicAcceptsNameAndBicParams(): void
    {
        $methods = $this->api->getMethodsList();
        $params = $methods['bic']['params'];
        $this->assertContains('name', $params);
        $this->assertContains('bic', $params);
    }

    public function testSearchAcceptsSearchStringParam(): void
    {
        $methods = $this->api->getMethodsList();
        $this->assertContains('SearchString', $methods['search']['params']);
    }

    // --- Method URL values ---

    public function testDailyUrlEnding(): void
    {
        $methods = $this->api->getMethodsList();
        $this->assertSame('XML_daily.asp', $methods['daily']['url']);
    }

    public function testCoinsUrlEnding(): void
    {
        $methods = $this->api->getMethodsList();
        $this->assertSame('XMLCoinsBase.asp', $methods['coins']['url']);
    }

    // --- Magic __call routing ---

    public function testCallingUnknownMethodThrowsCException(): void
    {
        $this->expectException(CException::class);
        $this->api->nonExistentMethod([]);
    }

    // --- getLatestUrl ---

    public function testLatestUrlIsNullBeforeAnyRequest(): void
    {
        $this->assertNull($this->api->getLatestUrl());
    }
}
