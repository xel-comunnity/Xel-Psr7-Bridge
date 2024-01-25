<?php

namespace Xel\Psr7bridge;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Xel\Psr7bridge\Contract\BridgeFactoryApp;
use Swoole\Http\Request as SwooleRequest;
use Swoole\Http\Response as SwooleResponse;
use Xel\Psr7bridge\Core\RequestMapper;
use Xel\Psr7bridge\Core\ResponseMapper;

final class PsrFactory implements BridgeFactoryApp
{
    private ?RequestMapper $mapper = null;
    private ?ResponseMapper $responseMapper = null;
    public function __construct
    (
        private $register
    ){}


    /*********************************************************
     * Request Mapper Field
     *********************************************************/
    public function connectRequest(SwooleRequest $request): ServerRequestInterface
    {
        // ? Convert Swoole request to PSR-7 ServerRequest
        if ($this->mapper === null){
            $this->mapper = new RequestMapper(
                $this->register->get('ServerFactory'),
                $this->register->get('StreamFactory'),
                $this->register->get('UploadFactory')
            );
        }
        return $this->mapper->serverMap(
            $request
        );

    }

    /*********************************************************
     * Response Mapper Field
     *********************************************************/

    public function connectResponse(ResponseInterface $psr7, SwooleResponse $swooleResponse): void
    {
        if ($this->responseMapper === null){
            $this->responseMapper = new ResponseMapper();
        }
        ($this->responseMapper)($psr7, $swooleResponse)->responseMap();
    }
}