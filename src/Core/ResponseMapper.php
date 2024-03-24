<?php

namespace Xel\Psr7bridge\Core;
use Dflydev\FigCookies\SetCookie;
use Dflydev\FigCookies\SetCookies;
use Psr\Http\Message\ResponseInterface;
use Swoole\Http\Response as SwooleResponse;

final class ResponseMapper
{
    private ResponseInterface $psr7Response;
    private SwooleResponse $swooleResponse;

    const THRESHOLD = 4098;
    public function __invoke
    (
        ResponseInterface $psr7Response,
        SwooleResponse $swooleResponse
    ): ResponseMapper
    {
        $this->psr7Response = $psr7Response;
        $this->swooleResponse = $swooleResponse;
        return $this;
    }

    public function responseMap(): void
    {
        $this->swooleResponse->status($this->psr7Response->getStatusCode(), $this->psr7Response->getReasonPhrase());

        foreach ($this->psr7Response->withoutHeader(SetCookies::SET_COOKIE_HEADER)->getHeaders() as $case => $value){
            $this->swooleResponse->header($case, implode(', ', $value));
        }

        foreach (SetCookies::fromResponse($this->psr7Response)->getAll() as $cookie){
            $this->cookieMap($this->swooleResponse, $cookie);
        }

        $this->mapBody($this->psr7Response, $this->swooleResponse);
    }

    private function cookieMap(swooleResponse $swooleResponse, SetCookie $cookie): void
    {
        $swooleResponse
            ->cookie
            (
                $cookie->getName(),
                $cookie->getPath(),
                $cookie->getExpires(),
                $cookie->getPath(),
                $cookie->getDomain(),
                $cookie->getSecure(),
                $cookie->getHttpOnly(),
                $cookie->getSameSite() ?? "",
            );
    }

    public function mapBody(ResponseInterface $response, SwooleResponse $swooleResponse): void
    {
        $body = $response->getBody();

        $allocChunk = $body->getSize();

        if ($allocChunk > self::THRESHOLD) {
            $this->chuckAllocator($body,$allocChunk,$swooleResponse);
        }

        if($body->isSeekable()){
            $body->rewind();
        }

        $swooleResponse->end($body->getContents());
    }

    private function chuckAllocator($body,$allocChunk, $swooleResponse): void
    {
        $getSizeChunk = intval($allocChunk * 0.25);
        while (!$body->eof()) {
            $chunk = $body->read($getSizeChunk);
            if ($chunk === '') {
                break;
            }
            $swooleResponse->write($chunk);
            $swooleResponse->end();
        }
    }

}