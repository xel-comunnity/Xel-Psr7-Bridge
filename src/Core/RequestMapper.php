<?php

namespace Xel\Psr7bridge\Core;

use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UploadedFileInterface;
use Swoole\Http\Request as SwooleRequest;
use RuntimeException;
final class RequestMapper
{
    private ServerRequestFactoryInterface $serverRequestFactory;
    private StreamFactoryInterface $streamFactory;
    private UploadedFileFactoryInterface $uploadedFileFactory;

    public function __construct
    (
        ServerRequestFactoryInterface $serverRequestFactory,
        StreamFactoryInterface $streamFactory,
        UploadedFileFactoryInterface $uploadedFileFactory
    )
    {
        $this->serverRequestFactory = $serverRequestFactory;
        $this->streamFactory = $streamFactory;
        $this->uploadedFileFactory = $uploadedFileFactory;
    }

    public function getStream(): StreamFactoryInterface
    {
        return $this->streamFactory;
    }

    public function getUploadFile(): UploadedFileFactoryInterface
    {
        return $this->uploadedFileFactory;
    }

    public function getServerRequest(): ServerRequestFactoryInterface
    {
        return $this->serverRequestFactory;
    }

    /**
     * @param SwooleRequest $swooleRequest
     * @return ServerRequestInterface|MessageInterface
     */
    public function serverMap
    (
        SwooleRequest $swooleRequest
    ): ServerRequestInterface|MessageInterface
    {
        // ? check swoole request
        $server = array_change_key_case($swooleRequest->server, CASE_UPPER);
        $mapper = $this->getServerRequest()
            ->createServerRequest
            (
                $server["REQUEST_METHOD"],
                $server["REQUEST_URI"],
                $server
            );

        // ? headerMap
        foreach ($swooleRequest->header as $case => $value) {
            $mapper =  $mapper->withHeader($case, $value);
        }

        // ? uploadMap
        $uploadFile = [];
        if (!empty($swooleRequest->files)){
            foreach ($swooleRequest->files as $case => $value) {
                $uploadFile[$case] = $this->createUploadedFileStream($value);
            }
        }

        // ? Map List
        return $mapper
            ->withCookieParams($swooleRequest->cookie ?? [])
            ->withQueryParams($swooleRequest->get ?? [])
            ->withParsedBody($swooleRequest->post ?? [])
            ->withBody($this->getStream()->createStream($swooleRequest->rawContent()))
            ->withUploadedFiles($uploadFile)
            ->withProtocolVersion('1.1');
    }

    
    private function createUploadedFileStream(array $files): StreamInterface|UploadedFileInterface
    {
        // Check if all required keys are present
        $requiredKeys = ['tmp_name', 'size', 'error', 'name', 'type'];
        if (array_diff($requiredKeys, array_keys($files))) {
            return $this->getStream()->createStream();
        }

        // Check if there is a file upload error
        if ($files['error'] !== UPLOAD_ERR_OK) {
            // Handle file upload error, log, or return a default value
            return $this->getStream()->createStream();
        }

        try {
            // Create a stream from the file
            $stream = $this->getStream()->createStreamFromFile($files['tmp_name']);
        } catch (RuntimeException) {
            // Handle runtime exception, log, or return a default value
            return $this->getStream()->createStream();
        }

        // Create and return an UploadedFileInterface
        return $this->getUploadFile()->createUploadedFile(
            $stream,
            $files['size'],
            $files['error'],
            $files['name'],
            $files['type']
        );
    }
}