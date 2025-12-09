<?php

declare(strict_types=1);

namespace NixPHP\Client\Core;

use NixPHP\Client\Exception\ClientException;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use function NixPHP\config;
use function NixPHP\json;
use function NixPHP\response;

class Client implements ClientInterface
{

    /**
     * @param RequestInterface $request
     * @param callable|null    $handler
     *
     * @return ResponseInterface
     * @throws ClientException
     */
    public function sendRequest(RequestInterface $request, ?callable $handler = null): ResponseInterface
    {
        $method  = strtoupper($request->getMethod());
        $url     = (string)$request->getUri();
        $headers = [];

        foreach ($request->getHeaders() as $name => $values) {
            foreach ($values as $value) {
                $headers[] = "$name: $value";
            }
        }

        $options = [
            'http' => [
                'method'        => $method,
                'header'        => implode("\r\n", $headers),
                'content'       => (string)$request->getBody(),
                'ignore_errors' => true
            ]
        ];

        if (false === config('client:ssl_verify', true)) {
            $options['ssl']['verify_peer']       = false;
            $options['ssl']['verify_peer_name']  = false;
            $options['ssl']['allow_self_signed'] = true;
        }

        if (null === $handler) {
            $handler = function($url, $options) use (&$http_response_header) {
                $context = stream_context_create($options);
                $body    = file_get_contents($url, false, $context);

                if ($body === false) {
                    throw new ClientException('HTTP request failed');
                }

                return [$body, $http_response_header ?? []];
            };
        }

        [$body, $responseHeadersRaw] = $handler($url, $options);

        if (!is_array($responseHeadersRaw) || count($responseHeadersRaw) < 1) {
            throw new ClientException('Handler must return array with [body, headers]');
        }

        $statusLine = $responseHeadersRaw[0] ?? '';
        preg_match('#HTTP/\d+\.\d+\s+(\d+)#', $statusLine, $matches);

        if (!isset($matches[1])) {
            throw new ClientException('Failed to parse HTTP status from response');
        }

        $responseHeaders = [];
        $status          = (int)($matches[1]);

        foreach ($responseHeadersRaw as $headerLine) {
            if (str_contains($headerLine, ':')) {
                [$name, $value] = explode(':', $headerLine, 2);
                $responseHeaders[trim($name)][] = trim($value);
            }
        }

        if (isset($responseHeaders['Content-Type']) && str_contains(implode(',', $responseHeaders['Content-Type']), 'application/json')) {
            return json($body, $status, $responseHeaders);
        }

        return response($body, $status, $responseHeaders);
    }
}
