<?php

use NixPHP\Client\Client;
use function NixPHP\app;

app()->container()->set('client', function() {
    return new Client();
});