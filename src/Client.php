<?php

namespace NixPHP\Client;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Nyholm\Psr7\Response;
use function NixPHP\config;

class Client implements ClientInterface
{
    public function sendRequest(RequestInterface $request, ?callable $handler = null): ResponseInterface
    {
        $method  = strtoupper($request->getMethod());
        $url     = (string) $request->getUri();
        $headers = [];

        foreach ($request->getHeaders() as $name => $values) {
            foreach ($values as $value) {
                $headers[] = "$name: $value";
            }
        }

        $options = [
            'http' => [
                'method'  => $method,
                'header'  => implode("\r\n", $headers),
                'content' => (string) $request->getBody(),
                'ignore_errors' => true
            ]
        ];

        if (false === config('client:ssl_verify', true)) {
            $options['ssl']['verify_peer'] = false;
            $options['ssl']['verify_peer_name'] = false;
            $options['ssl']['allow_self_signed'] = true;
        }

        if (null === $handler) {
            $handler = function($url, $options) {
                $context = stream_context_create($options);
                $body = file_get_contents($url, false, $context);
                return [$body, $http_response_header];
            };
        }

        [$body, $responseHeadersRaw] = $handler($url, $options);

        $statusLine = $responseHeadersRaw[0] ?? 'HTTP/1.1 200 OK';
        preg_match('#HTTP/\d+\.\d+\s+(\d+)#', $statusLine, $matches);
        $status = (int)($matches[1] ?? 200);

        $responseHeaders = [];

        foreach ($responseHeadersRaw as $headerLine) {
            if (str_contains($headerLine, ':')) {
                [$name, $value] = explode(':', $headerLine, 2);
                $responseHeaders[trim($name)][] = trim($value);
            }
        }

        return new Response($status, $responseHeaders, $body);
    }
}