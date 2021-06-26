<?php

declare(strict_types=1);

namespace LuciRpc\Exceptions;

class RpcRequestException extends \Exception
{
    private mixed $data;

    #[\JetBrains\PhpStorm\Pure]
    public function __construct(array $error)
    {
        $this->data = $error['data'] ?? null;
        parent::__construct($error['message'] ?? 'Rpc request error', $error['code'] ?? -1);
    }

    public function getErrorData(): mixed
    {
        return $this->data;
    }
}
