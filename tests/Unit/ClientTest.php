<?php

declare(strict_types=1);

namespace Tests\Unit;

use NixPHP\Client\Exception\ClientException;
use Nyholm\Psr7\Request;
use NixPHP\Client\Core\Client;
use NixPHP\Core\Config;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Tests\NixPHPTestCase;
use function NixPHP\app;
use function NixPHP\Client\client;

class ClientTest extends NixPHPTestCase
{

    public function testClientResponse()
    {
        $client = new Client();
        $request = new Request('GET', '/test');
        $response = $client->sendRequest($request, function () {
            return [
                'test',
                [
                    'HTTP/1.1 200 OK',
                    'Content-Type: text/plain'
                ]
            ];
        });

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testSslIgnore()
    {
        $config = new Config(['client' => ['ssl_verify' => false]]);
        app()->container()->set('config', $config);

        $client = new Client();
        $request = new Request('GET', '/test');
        $response = $client->sendRequest($request, function () {
            return [
                'test',
                [
                    'HTTP/1.1 200 OK',
                    'Content-Type: text/plain'
                ]
            ];
        });
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testRequestWithHeaders()
    {
        $request = new Request('GET', '/test');
        $request = $request->withHeader('Content-Type', 'text/plain');

        $client = new Client();
        $response = $client->sendRequest($request, function () {
            return [
                'test', [
                    'HTTP/1.1 200 OK',
                    'Content-Type: text/plain'
                ]
            ];
        });

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testRequestWithFormatJSON()
    {
        $request = new Request('GET', '/test');
        $request = $request->withHeader('Content-Type', 'application/json');

        $client = new Client();
        $response = $client->sendRequest($request, function () {
            return [
                ['test' => 'test'],
                [
                    'HTTP/1.1 200 OK',
                    'Content-Type: application/json'
                ]
            ];
        });

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertJsonStringEqualsJsonString('{"test": "test"}', $response->getBody()->getContents());
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testClientExceptionOnInvalidResponse()
    {
        $this->expectException(ClientExceptionInterface::class);
        $request = new Request('GET', '/test');
        $request = $request->withHeader('Content-Type', 'text/plain');

        $client = new Client();
        $client->sendRequest($request, function () {
            throw new ClientException('Invalid response');
        });
    }

    public function testClientExceptionOnMissingResponseStatusCode()
    {
        $this->expectException(ClientExceptionInterface::class);
        $request = new Request('GET', '/test');
        $request = $request->withHeader('Content-Type', 'text/plain');

        $client = new Client();
        $client->sendRequest($request, function () {
            return [
                'test', [
                    '',
                    'Content-Type: text/plain'
                ]
            ];
        });
    }

    public function testHelperFunction()
    {
        $this->assertInstanceOf(Client::class, client());
    }

}