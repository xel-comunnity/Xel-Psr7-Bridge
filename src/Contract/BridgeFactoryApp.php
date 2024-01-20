<?php

namespace Xel\Psr7bridge\Contract;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Swoole\Http\Request as SwooleRequest;
use Swoole\Http\Response as SwooleResponse;
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