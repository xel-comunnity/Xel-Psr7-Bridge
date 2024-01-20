<?php

namespace Xel\Psr7bridge;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Xel\Psr7bridge\Contract\BridgeFactoryApp;
use Swoole\Http\Request as SwooleRequest;
use Swoole\Http\Response as SwooleResponse;
use Xel\Psr7bridge\Core\RequestMapper;
use Xel\Psr7bridge\Core\ResponseMapper;

readonly class PsrFactory implements BridgeFactoryApp
{
  public function __construct
  (
    private ServerRequestFactoryInterface $serverRequestFactory,
    private StreamFactoryInterface        $streamFactory,
    private UploadedFileFactoryInterface  $uploadedFileFactory,

  ){}
    /*********************************************************
     * Request Mapper Field
     *********************************************************/
    public function connectRequest(SwooleRequest $request): ServerRequestInterface
    {
        // ? Convert Swoole request to PSR-7 ServerRequest
        return $this->createServerRequestFromSwoole($request);
    }

    private function createServerRequestFromSwoole(SwooleRequest $swooleRequest): ServerRequestInterface
    {
        // ? instance of mapper
        $Mapper = new RequestMapper();

        // ? Map Swoole Request to PSR 7
        return $Mapper(
            $this->serverRequestFactory,
            $this->streamFactory,
            $this->uploadedFileFactory
        )->serverMap
        (
            array_change_key_case($swooleRequest->server, CASE_UPPER),
            $swooleRequest->header ?? [],
            $swooleRequest->cookie ?? [],
            $swooleRequest->get ?? [],
            $swooleRequest->post ?? [],
            $swooleRequest->files ?? [],
            $swooleRequest->rawContent()
        );
    }

    /*********************************************************
     * Response Mapper Field
     *********************************************************/

    public function connectResponse(ResponseInterface $psr7, SwooleResponse $swooleResponse): void
    {

        $this->createResponseRequestFromSwoole($psr7, $swooleResponse);
    }

    private function createResponseRequestFromSwoole(ResponseInterface $response,SwooleResponse $swooleResponse): void
    {
        $Mapper = new ResponseMapper();
        $Mapper($response,$swooleResponse)->responseMap();
    }
}