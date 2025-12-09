<?php

declare(strict_types=1);

namespace NixPHP\Client\Exception;

use Psr\Http\Client\ClientExceptionInterface;

class ClientException extends \Exception implements ClientExceptionInterface
{

}