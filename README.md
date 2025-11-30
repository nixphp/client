<div style="text-align: center;">

![Logo](https://nixphp.github.io/docs/assets/nixphp-logo-small-square.png)

[![NixPHP Client Plugin](https://github.com/nixphp/client/actions/workflows/php.yml/badge.svg)](https://github.com/nixphp/client/actions/workflows/php.yml)

</div>

[← Back to NixPHP](https://github.com/nixphp/framework)

---

# nixphp/client

> **Simple HTTP client for PSR-18 requests — the NixPHP way.**

This plugin provides a lightweight and dependency-free implementation
of the `Psr\Http\Client\ClientInterface`, using native PHP streams.

> 🧩 Part of the official NixPHP plugin collection.
> Perfect for internal API calls, simple integrations, and testing purposes.

---

## 📦 Features

* ✅ Implements `Psr\Http\Client\ClientInterface`
* ✅ No cURL, no external dependencies — pure PHP
* ✅ Supports custom handlers for testing/mocking
* ✅ Optional SSL verification toggle via config
* ✅ Integrates cleanly via `client()` helper

---

## 📥 Installation

```bash
composer require nixphp/client
```

That’s it. The plugin will be autoloaded and ready to use.

---

## 🚀 Usage

### ✉️ Send a PSR-7 request

```php
use Nyholm\Psr7\Request;

$request = new Request('GET', 'https://example.com/api');
$response = client()->sendRequest($request);

echo $response->getStatusCode();
echo (string) $response->getBody();
```

You can also pass a custom handler (e.g. for unit tests):

```php
$response = client()->sendRequest($request, function($url, $opts) {
    return ['{"mock":true}', ['HTTP/1.1 200 OK']];
});
```

---

## ⚙️ Configuration

Disable SSL peer verification (e.g. for local dev):

```php
// app/config.php
return [
    'ssl_verify' => false
];
```

This disables `verify_peer`, `verify_peer_name`, and allows self-signed certs.

---

## 🔍 Internals

* Uses `file_get_contents()` with PHP stream context.
* Parses raw headers into a PSR-7-compatible `Response` object.
* Default response class: `Nyholm\Psr7\Response`
* Automatically included via `client()` helper.

---

## ✅ Requirements

* `nixphp/framework` >= 0.1.0
* `nyholm/psr7` >= 1.0 (used for PSR-7 implementation)

---

## 📄 License

MIT License.