<?php

declare(strict_types=1);

namespace Leamix\XmlService;

use Leamix\XmlService\Exception\ServiceException;

/**
 * Abstract base class for XML service adapters.
 *
 * Subclasses must define $url and may override getMethodsList() to expose
 * named request methods via __call().
 *
 * @property-read string|null $latestUrl Last URL that was requested
 */
abstract class ServiceApi
{
    /** @var string Base URL of the service — must be overridden in the subclass */
    protected string $url = '';

    private ?string $latestUrl = null;

    public function __construct()
    {
        if ($this->url === '') {
            throw new ServiceException(
                'The URL must be specified in ' . static::class . '.',
            );
        }
    }

    public function getLatestUrl(): ?string
    {
        return $this->latestUrl;
    }

    /**
     * HTTP context options sent with every request.
     * Override to add authentication headers, proxy settings, etc.
     *
     * @return array
     */
    protected function getHeaders(): array
    {
        return [
            'http' => [
                'method' => 'GET',
            ],
        ];
    }

    /**
     * Sends a GET request and returns the parsed or raw response.
     *
     * @param array $params Query-string parameters
     * @param string $url Override URL; defaults to $this->url
     * @param bool $parse true → return array, false → return raw XML string
     * @return array|string
     * @throws ServiceException on connection failure
     */
    protected function response(array $params = [], string $url = '', bool $parse = true)
    {
        if ($url === '') {
            $url = $this->url;
        }

        $query = !empty($params) ? '?' . http_build_query($params) : '';
        $this->latestUrl = $url . $query;

        $response = @file_get_contents(
            $this->latestUrl,
            false,
            stream_context_create($this->getHeaders()),
        );

        if ($response === false) {
            throw new ServiceException('URL ' . $this->latestUrl . ' is not responding.');
        }

        return $parse ? $this->parseResponse($response) : $response;
    }

    /**
     * Converts an XML string into a nested associative array.
     *
     * @param string $xmldata
     * @return array
     * @throws ServiceException on malformed XML
     */
    public function parseResponse(string $xmldata): array
    {
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($xmldata);
        libxml_clear_errors();

        if ($xml === false) {
            throw new ServiceException('Failed to parse XML response.');
        }

        return (array)json_decode((string)json_encode($xml), true);
    }

    /**
     * Returns the map of named methods available on this service.
     *
     * Format:
     * [
     *     'method_name' => [
     *         'url'    => 'endpoint.asp',
     *         'params' => ['param1', 'param2'],
     *     ],
     * ]
     *
     * @return array
     */
    public function getMethodsList(): array
    {
        return [];
    }

    /**
     * Filters $array to only include keys declared in the named method's params list.
     *
     * @param string $name Method name from getMethodsList()
     * @param array $array Input parameters
     * @return array
     */
    protected function checkParams(string $name, array $array): array
    {
        if ($name === '' || empty($array)) {
            return [];
        }

        $methods = $this->getMethodsList();

        if (!isset($methods[$name])) {
            return [];
        }

        $allowed = $methods[$name]['params'];
        $data = [];

        foreach ($array as $key => $value) {
            if (in_array($key, $allowed, true)) {
                $this->checkParamType($data, $key, $value, $name);
            }
        }

        return $data;
    }

    /**
     * Validates and transforms a single parameter value.
     * Override to coerce types or apply format rules.
     *
     * @param array $data Reference to the final parameter array
     * @param string $key Parameter name
     * @param mixed $param Parameter value
     * @param string $methodName Method name from getMethodsList()
     */
    protected function checkParamType(array &$data, string $key, $param, string $methodName): void
    {
        $data[$key] = $param;
    }
}
