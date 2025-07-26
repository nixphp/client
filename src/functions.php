<?php

use function NixPHP\app;

function client(): NixPHP\Client\Client
{
    return app()->container()->get('client');
}