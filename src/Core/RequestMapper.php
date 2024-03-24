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
final readonly class RequestMapper
{
    public function __construct
    (
        private ServerRequestFactoryInterface $serverRequestFactory,
        private StreamFactoryInterface        $streamFactory,
        private UploadedFileFactoryInterface  $uploadedFileFactory,
    )
    {

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
        // Check Swoole request
        $server = array_change_key_case($swooleRequest->server, CASE_UPPER);
        $serverRequestFactory = $this->getServerRequest();
        $mapper = $serverRequestFactory
            ->createServerRequest(
                $server["REQUEST_METHOD"],
                $server["REQUEST_URI"],
                $server
            );

        // Map headers
        foreach ($swooleRequest->header as $case => $value) {
            $mapper = $mapper->withHeader($case, $value);
        }

        // Map cookies
        $cookies = $swooleRequest->cookie ?? [];
        $mapper = $mapper->withCookieParams($cookies);

        // Map query parameters
        $queryParams = $swooleRequest->get ?? [];
        $mapper = $mapper->withQueryParams($queryParams);

        // Map parsed body (for POST requests)
        if ($swooleRequest->getMethod() === 'POST') {
            $parsedBody = $swooleRequest->post ?? [];
            $mapper = $mapper->withParsedBody($parsedBody);
        }

        // Map uploaded files
        $uploadFile = [];
        if (!empty($swooleRequest->files)) {
            foreach ($swooleRequest->files as $case => $value) {
                $uploadFile[$case] = $this->createUploadedFileStream($value);
            }
        }

        // Map the rest
        return $mapper
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