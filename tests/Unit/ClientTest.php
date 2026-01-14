<?php

declare(strict_types=1);

namespace Tests\Unit;

use NixPHP\Client\Core\Client;
use NixPHP\Core\Config;
use Nyholm\Psr7\Request;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Fixtures\Transports\MockTransport;
use Tests\NixPHPTestCase;
use function NixPHP\app;
use function NixPHP\Client\client;

final class ClientTest extends NixPHPTestCase
{
    public function testClientResponse(): void
    {
        $transport = new MockTransport();
        $transport->pushResponse('test', [
            'HTTP/1.1 200 OK',
            'Content-Type: text/plain',
        ]);

        $client = new Client([$transport]);

        $response = $client->sendRequest(new Request('GET', 'https://example.com/test'));

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(1, $transport->calls());
    }

    public function testRequestWithFormatJSON(): void
    {
        $transport = new MockTransport();
        $transport->pushResponse('{"test":"test"}', [
            'HTTP/1.1 200 OK',
            'Content-Type: application/json',
        ]);

        $client = new Client([$transport]);

        $response = $client->sendRequest(new Request('GET', 'https://example.com/test'));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString('{"test":"test"}', (string)$response->getBody());
    }

    public function testClientExceptionOnMissingResponseStatusCode(): void
    {
        $this->expectException(ClientExceptionInterface::class);

        $transport = new MockTransport();
        $transport->pushResponse('test', [
            '',
            'Content-Type: text/plain',
        ]);

        $client = new Client([$transport]);
        $client->sendRequest(new Request('GET', 'https://example.com/test'));
    }

    public function testClientExceptionWrapsTransportException(): void
    {
        $this->expectException(ClientExceptionInterface::class);

        $transport = new MockTransport();
        $transport->pushError(new \RuntimeException('Boom'));

        $client = new Client([$transport]);
        $client->sendRequest(new Request('GET', 'https://example.com/test'));
    }

    public function testRetryOnEnableCryptoThenSuccess(): void
    {
        // Configure retries so we actually retry once.
        $config = new Config([
            'client' => [
                'retries' => 1,
                'retry_delay_ms' => 0,
            ],
        ]);
        app()->container()->set('config', $config);

        $transport = new MockTransport();
        $transport->pushError(new \RuntimeException('file_get_contents() failed to enable crypto'));
        $transport->pushResponse('ok', [
            'HTTP/1.1 200 OK',
            'Content-Type: text/plain',
        ]);

        $client = new Client([$transport]);
        $response = $client->sendRequest(new Request('GET', 'https://example.com/test'));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('ok', (string) $response->getBody());
        $this->assertSame(2, $transport->calls());
    }

    public function testNoRetryOnNonTransientError(): void
    {
        $this->expectException(ClientExceptionInterface::class);

        $config = new Config([
            'client' => [
                'retries' => 3,
                'retry_delay_ms' => 0,
            ],
        ]);
        app()->container()->set('config', $config);

        $transport = new MockTransport();
        $transport->pushError(new \RuntimeException('Invalid response schema')); // not transient

        $client = new Client([$transport]);
        $client->sendRequest(new Request('GET', 'https://example.com/test'));

        $this->assertSame(1, $transport->calls());
    }

    public function testPickTransportSelectsFirstAvailable(): void
    {
        $t1 = new MockTransport(false); // unavailable
        $t2 = new MockTransport(true);
        $t2->pushResponse('ok', [
            'HTTP/1.1 200 OK',
            'Content-Type: text/plain',
        ]);

        $client = new Client([$t1, $t2]);

        $response = $client->sendRequest(new Request('GET', 'https://example.com/test'));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(0, $t1->calls());
        $this->assertSame(1, $t2->calls());
    }

    public function testSslIgnoreConfigDoesNotCrash(): void
    {
        // We cannot easily assert transport TLS flags here (that's transport unit territory),
        // but we can ensure config is accepted and request still works.
        $config = new Config(['client' => ['ssl_verify' => false]]);
        app()->container()->set('config', $config);

        $transport = new MockTransport();
        $transport->pushResponse('test', [
            'HTTP/1.1 200 OK',
            'Content-Type: text/plain',
        ]);

        $client = new Client([$transport]);
        $response = $client->sendRequest(new Request('GET', 'https://example.com/test'));

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testHelperFunction(): void
    {
        $this->assertInstanceOf(Client::class, client());
    }
}
