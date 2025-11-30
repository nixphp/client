<?php

declare(strict_types=1);

use NixPHP\Client\Core\Client;
use function NixPHP\app;

app()->container()->set(Client::class, fn() => new Client());