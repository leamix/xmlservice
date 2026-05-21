<?php

declare(strict_types=1);

namespace Leamix\XmlService\Http;

use Leamix\XmlService\Exception\ServiceException;

class DefaultHttpClient implements HttpClientInterface
{
    private const USER_AGENT = 'leamix/xmlservice';

    private int $timeout;

    public function __construct(int $timeout = 30)
    {
        $this->timeout = $timeout;
    }

    public function get(string $url): string
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => $this->timeout,
                'user_agent' => self::USER_AGENT,
                'ignore_errors' => true,
            ],
        ]);

        error_clear_last();
        $body = @file_get_contents($url, false, $context);

        if ($body === false) {
            $err = error_get_last();
            throw new ServiceException(
                $err !== null
                    ? "Request to {$url} failed: {$err['message']}"
                    : "No response from {$url}.",
            );
        }

        // $http_response_header is set as a local variable by the HTTP stream wrapper
        if (!empty($http_response_header)) {
            preg_match('#HTTP/\S+\s+(\d+)#', $http_response_header[0], $m);
            $status = (int)($m[1] ?? 200);
            if ($status >= 400) {
                throw new ServiceException("HTTP {$status} from {$url}.");
            }
        }

        return $body;
    }
}
