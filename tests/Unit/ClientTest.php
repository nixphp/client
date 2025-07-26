<?php

namespace Tests\Unit;

use Nyholm\Psr7\Request;
use NixPHP\Core\Client;
use NixPHP\Core\Config;
use Psr\Http\Message\ResponseInterface;
use Tests\NixPHPTestCase;
use function NixPHP\app;

class ClientTest extends NixPHPTestCase
{

    public function testClientResponse()
    {
        $client = new Client();
        $request = new Request('GET', '/test');
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

    public function testSslIgnore()
    {
        $config = new Config(['client' => ['ssl_verify' => false]]);
        app()->container()->set('config', $config);

        $client = new Client();
        $request = new Request('GET', '/test');
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

}