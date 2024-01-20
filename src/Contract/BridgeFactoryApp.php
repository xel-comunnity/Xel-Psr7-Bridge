<?php

namespace Xel\Psr7bridge\Contract;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Swoole\Http\Request as SwooleRequest;
use Swoole\Http\Response as SwooleResponse;
use Xel\Psr7bridge\PsrFactory;

interface BridgeFactoryApp
{

    public function connectRequest
    (
        SwooleRequest $request,
    ):ServerRequestInterface;

    public function connectResponse
    (
        ResponseInterface $psr7,
        SwooleResponse $swooleResponse,
    ):void;
}