<?php

declare(strict_types=1);

namespace Leamix\XmlService\Tests;

use Leamix\XmlService\CbrApi;
use Leamix\XmlService\Exception\ServiceException;
use Leamix\XmlService\ServiceApi;
use PHPUnit\Framework\TestCase;

class CbrApiTest extends TestCase
{
    /** @var CbrApi */
    private $api;

    protected function setUp(): void
    {
        $this->api = new CbrApi();
    }

    // --- Instantiation ---

    public function testInstantiatesSuccessfully(): void
    {
        $this->assertInstanceOf(CbrApi::class, $this->api);
        $this->assertInstanceOf(ServiceApi::class, $this->api);
    }

    // --- getMethodsList ---

    public function testMethodsListContainsAllExpectedKeys(): void
    {
        $methods  = $this->api->getMethodsList();
        $expected = ['daily', 'daily_eng', 'dynamic', 'ostat', 'mkr', 'depo', 'search', 'news', 'bic', 'swap', 'coins'];

        foreach ($expected as $name) {
            $this->assertArrayHasKey($name, $methods, "Method '{$name}' not found in methodsList");
        }
    }

    public function testMethodsListHasExactlyElevenEntries(): void
    {
        $this->assertCount(11, $this->api->getMethodsList());
    }

    public function testEveryMethodEntryHasUrlAndParamsKeys(): void
    {
        foreach ($this->api->getMethodsList() as $name => $entry) {
            $this->assertArrayHasKey('url', $entry, "Method '{$name}' missing 'url'");
            $this->assertArrayHasKey('params', $entry, "Method '{$name}' missing 'params'");
            $this->assertIsString($entry['url'], "'{$name}'.url must be a string");
            $this->assertIsArray($entry['params'], "'{$name}'.params must be an array");
        }
    }

    // --- Method-specific params ---

    public function testDailyAcceptsDateReq(): void
    {
        $this->assertContains('date_req', $this->api->getMethodsList()['daily']['params']);
    }

    public function testDailyEngAcceptsDateReq(): void
    {
        $this->assertContains('date_req', $this->api->getMethodsList()['daily_eng']['params']);
    }

    public function testDynamicAcceptsDateRangeAndCurrencyCode(): void
    {
        $params = $this->api->getMethodsList()['dynamic']['params'];
        $this->assertContains('date_req1', $params);
        $this->assertContains('date_req2', $params);
        $this->assertContains('VAL_NM_RQ', $params);
    }

    public function testOstatAcceptsDateRange(): void
    {
        $params = $this->api->getMethodsList()['ostat']['params'];
        $this->assertContains('date_req1', $params);
        $this->assertContains('date_req2', $params);
    }

    public function testNewsHasNoParams(): void
    {
        $this->assertSame([], $this->api->getMethodsList()['news']['params']);
    }

    public function testBicAcceptsNameAndBic(): void
    {
        $params = $this->api->getMethodsList()['bic']['params'];
        $this->assertContains('name', $params);
        $this->assertContains('bic', $params);
    }

    public function testSearchAcceptsSearchString(): void
    {
        $this->assertContains('SearchString', $this->api->getMethodsList()['search']['params']);
    }

    // --- URL values ---

    public function testDailyUrlEndpoint(): void
    {
        $this->assertSame('XML_daily.asp', $this->api->getMethodsList()['daily']['url']);
    }

    public function testCoinsUrlEndpoint(): void
    {
        $this->assertSame('XMLCoinsBase.asp', $this->api->getMethodsList()['coins']['url']);
    }

    // --- Magic __call routing ---

    public function testCallingUnknownMethodThrowsServiceException(): void
    {
        $this->expectException(ServiceException::class);
        $this->api->nonExistentMethod([]);
    }

    public function testExceptionMessageContainsMethodName(): void
    {
        try {
            $this->api->nonExistentMethod([]);
            $this->fail('Expected ServiceException was not thrown');
        } catch (ServiceException $e) {
            $this->assertStringContainsString('nonExistentMethod', $e->getMessage());
        }
    }

    // --- getLatestUrl ---

    public function testLatestUrlIsNullBeforeAnyRequest(): void
    {
        $this->assertNull($this->api->getLatestUrl());
    }
}
