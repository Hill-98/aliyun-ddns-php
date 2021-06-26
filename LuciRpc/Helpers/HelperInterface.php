<?php

declare(strict_types=1);

namespace LuciRpc\Helpers;

use LuciRpc\Client;

interface HelperInterface
{
    public function __construct(Client $client);
}
