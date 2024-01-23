<?php

namespace Xel\Psr7bridge;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Xel\Psr7bridge\Contract\BridgeFactoryApp;
use Swoole\Http\Request as SwooleRequest;
use Swoole\Http\Response as SwooleResponse;
use Xel\Psr7bridge\Core\RequestMapper;
use Xel\Psr7bridge\Core\ResponseMapper;

readonly class PsrFactory implements BridgeFactoryApp
{
  public function __construct
  (
    private RequestMapper $mapper,
    private ResponseMapper $responseMapper
  ){}
    /*********************************************************
     * Request Mapper Field
     *********************************************************/
    public function connectRequest(SwooleRequest $request): ServerRequestInterface
    {
        // ? Convert Swoole request to PSR-7 ServerRequest
        return $this->mapper->serverMap(
            $request
        );

    }

    /*********************************************************
     * Response Mapper Field
     *********************************************************/

    public function connectResponse(ResponseInterface $psr7, SwooleResponse $swooleResponse): void
    {

        $this->createResponseRequestFromSwoole($psr7, $swooleResponse);
    }



    private function createResponseRequestFromSwoole(ResponseInterface $response, SwooleResponse $swooleResponse): void
    {
        $Mapper = $this->responseMapper;
        $Mapper($response,$swooleResponse)->responseMap();
    }
}