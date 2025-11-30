<?php

declare(strict_types=1);

namespace NixPHP\Client;

use NixPHP\Client\Core\Client;
use function NixPHP\app;

function client(): Client
{
    return app()->container()->get(Client::class);
}