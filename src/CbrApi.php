<?php

declare(strict_types=1);

namespace Leamix\XmlService;

use JsonException;
use Leamix\XmlService\Exception\ServiceException;
use Leamix\XmlService\Http\DefaultHttpClient;
use Leamix\XmlService\Http\HttpClientInterface;
use Leamix\XmlService\Request\RequestInterface;

/**
 * Adapter for the Central Bank of Russia XML service.
 *
 * Inject any HttpClientInterface implementation to replace the built-in
 * file_get_contents transport — useful for adding caching, retries, logging,
 * or using a PSR-18 client (Guzzle, Symfony HttpClient, etc.) via a thin adapter.
 *
 * @see https://www.cbr.ru/development/SXML/
 */
class CbrApi
{
    private const BASE_URL = 'https://www.cbr.ru/scripts/';

    private HttpClientInterface $http;

    public function __construct(?HttpClientInterface $http = null)
    {
        $this->http = $http ?? new DefaultHttpClient();
    }

    /**
     * @throws ServiceException on connection failure or malformed XML
     */
    public function send(RequestInterface $request): array
    {
        return $this->parseResponse($this->sendRaw($request));
    }

    /**
     * @throws ServiceException on connection failure
     */
    public function sendRaw(RequestInterface $request): string
    {
        $params = $request->getParams();
        $query = $params !== [] ? '?' . http_build_query($params) : '';
        $url = self::BASE_URL . $request->getEndpoint() . $query;

        return $this->http->get($url);
    }

    /**
     * Convert a raw XML string into a nested associative array.
     *
     * Uses the json_encode/json_decode roundtrip: XML attributes are exposed as
     * '@attributes' keys and repeated sibling elements are collapsed into indexed
     * sub-arrays by SimpleXML.
     *
     * @throws ServiceException on malformed XML or encoding failure
     */
    public function parseResponse(string $xml): array
    {
        $previous = libxml_use_internal_errors(true);
        $element = simplexml_load_string($xml);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if ($element === false) {
            throw new ServiceException('Failed to parse XML response.');
        }

        try {
            $data = json_decode(json_encode($element, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new ServiceException('Failed to encode/decode XML: ' . $e->getMessage());
        }

        if (!is_array($data)) {
            throw new ServiceException('XML encoded to a non-array value.');
        }

        return $data;
    }
}
