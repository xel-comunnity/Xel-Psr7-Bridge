<?php

use HttpSoft\Message\ResponseFactory;
use HttpSoft\Message\ServerRequestFactory;
use HttpSoft\Message\StreamFactory;
use HttpSoft\Message\UploadedFileFactory;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Xel\Psr7bridge\Test\Container\Register;

require __DIR__ . "/../../vendor/autoload.php";

$server = new Swoole\Http\Server('0.0.0.0', 9501, 2);
$server->set([
    'upload_tmp_dir' => __DIR__."/tmp/",
    "worker_num" => 35
]);

$register = new Register();

// ? Register the factories in the container
$register->register('ServerFactory', ServerRequestFactory::class);
$register->register('StreamFactory', StreamFactory::class);
$register->register('UploadFactory', UploadedFileFactory::class);
$register->register("ResponseFactory", ResponseFactory::class);

// ? Resolve the dependencies from the container
$psrRequest = $register->resolve('ServerFactory');
$psrStream = $register->resolve('StreamFactory');
$psrUpload = $register->resolve('UploadFactory');
$psrResponse = $register->resolve('ResponseFactory');


$psrBridge = new Xel\Psr7bridge\PsrFactory($register);

$server->on('start', function () {
    echo "Listening at http://localhost:9501\n";
});

$server->on('request', function (Request $req, Response $res) use ($psrBridge, $psrResponse, $psrStream) {

        $bridgeRequest = $psrBridge->connectRequest($req);
        $response = $psrResponse->createResponse();

        if ($bridgeRequest->getMethod() == "POST") {
            // Perform async operations here

            // Send the response
            $response = $response->withHeader('Content-Type', 'application/json')
                ->withStatus(200)
                ->withBody($psrStream->createStream($bridgeRequest->getBody()->getContents()));

            $psrBridge->connectResponse($response, $res);
        }
});

$server->start();
