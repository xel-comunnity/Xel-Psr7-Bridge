<?php

namespace Xel\Psr7bridge\Core;

use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UploadedFileInterface;
use RuntimeException;

class RequestMapper
{
    private ServerRequestFactoryInterface $serverRequestFactory;
    private StreamFactoryInterface $streamFactory;
    private UploadedFileFactoryInterface $uploadedFileFactory;
    public array $uploadFile;

    public function __invoke
    (
        ServerRequestFactoryInterface $serverRequestFactory,
        StreamFactoryInterface $streamFactory,
        UploadedFileFactoryInterface $uploadedFileFactory
    ): RequestMapper
    {
        $this->serverRequestFactory = $serverRequestFactory;
        $this->streamFactory = $streamFactory;
        $this->uploadedFileFactory = $uploadedFileFactory;
        return $this;
    }

    public function serverMap
    (
        array $swooleRequest,
        array $swooleHeader,
        array $swooleCookie,
        array $swooleQueryParam,
        array $swooleParseBody,
        array $swooleUploadFiles,
        string|false $rawContent
    ): ServerRequestInterface
    {
        $mapper = $this->serverRequestFactory
            ->createServerRequest
            (
                $swooleRequest["REQUEST_METHOD"],
                $swooleRequest["REQUEST_URI"],
                $swooleRequest
            );

        // ? headerMap
        foreach ($swooleHeader as $case => $value) {
            $mapper =  $mapper->withHeader($case, $value);
        }

        // ? uploadMap
        if (!empty($swooleUploadFiles)){
            foreach ($swooleUploadFiles as $case => $value) {
                $this->uploadFile[$case] = $this->createUploadedFileStream($value);
            }
        }

        // ? Map List
        return $mapper
            ->withCookieParams($swooleCookie ??[])
            ->withQueryParams($swooleQueryParam ?? [])
            ->withParsedBody($swooleParseBody ?? [])
            ->withBody($this->streamFactory->createStream($rawContent))
            ->withUploadedFiles($this->uploadFile ?? [])
            ->withProtocolVersion('1.1');
    }

    private function createUploadedFileStream(array $files): StreamInterface|UploadedFileInterface
    {
        try {
            $stream = $this->streamFactory->createStreamFromFile($files['tmp_name']);
        } catch (RuntimeException) {
            $stream = $this->streamFactory->createStream();
        }

        return $this->uploadedFileFactory->createUploadedFile(
            $stream,
            $files['size'],
            $files['error'],
            $files['name'],
            $files['type']
        );
    }
}