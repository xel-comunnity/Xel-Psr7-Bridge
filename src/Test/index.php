<?php


use HttpSoft\Message\ResponseFactory;
use HttpSoft\Message\ServerRequestFactory;
use HttpSoft\Message\StreamFactory;
use HttpSoft\Message\UploadedFileFactory;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Xel\Psr7bridge\Core\RequestMapper;
use Xel\Psr7bridge\Core\ResponseMapper;

require __DIR__ . "/../../vendor/autoload.php";

$server = new Swoole\Http\Server('0.0.0.0', 9501);

$server->on('start', function () {
    echo "Listening at http://localhost:9501\n";
});

$psrBridge = new Xel\Psr7bridge\PsrFactory(
  new RequestMapper(
      new ServerRequestFactory(),
      new StreamFactory(),
      new UploadedFileFactory()
  ),
 new ResponseMapper()
);

$psrResponse = new ResponseFactory();
$psrStream = new StreamFactory();

$server->on('request', function (Request $req, Response $res) use ($psrBridge, $psrResponse, $psrStream) {
   $Bride =  $psrBridge->connectRequest($req);
   $Response = $psrResponse->createResponse();

   $Response = $Response->withStatus(200);
   $Response = $Response->withBody($psrStream->createStream(json_encode("hello world")));

   $psrBridge->connectResponse($Response, $res);
});

$server->start();
