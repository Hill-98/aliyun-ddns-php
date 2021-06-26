<?php

declare(strict_types=1);

namespace LuciRpc\Helpers;

use LuciRpc\Client;

abstract class Helper implements HelperInterface
{
    public function __construct(protected Client $client)
    {
    }
}
