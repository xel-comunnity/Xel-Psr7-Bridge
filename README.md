<br/>
<p align="center">
  <a href="https://github.com/Bxel07/Xel-Psr7-Bridge">
    <img src="images/logo.png" alt="Logo" width="80" height="80">
  </a>

  <h3 align="center">Swoole Psr7 Bridge</h3>

  <p align="center">
    This library still not for production. The Idea of this library is to process copy Swoole Http response to psr 7 standart when needed and leverage  this copy using Psr 17 factory. in response, it will have dynamic switching when the response content have the larger byte value, it will split and make it chunk to make it more light to send.
    <br/>
    <br/>
    <a href="https://github.com/Bxel07/Xel-Psr7-Bridge"><strong>Explore the docs »</strong></a>
    <br/>
    <br/>
    <a href="https://github.com/Bxel07/Xel-Psr7-Bridge">View Demo</a>
    .
    <a href="https://github.com/Bxel07/Xel-Psr7-Bridge/issues">Report Bug</a>
    .
    <a href="https://github.com/Bxel07/Xel-Psr7-Bridge/issues">Request Feature</a>
  </p>
</p>

![Downloads](https://img.shields.io/github/downloads/Bxel07/Xel-Psr7-Bridge/total) ![Contributors](https://img.shields.io/github/contributors/Bxel07/Xel-Psr7-Bridge?color=dark-green) ![Forks](https://img.shields.io/github/forks/Bxel07/Xel-Psr7-Bridge?style=social) ![Stargazers](https://img.shields.io/github/stars/Bxel07/Xel-Psr7-Bridge?style=social) ![Issues](https://img.shields.io/github/issues/Bxel07/Xel-Psr7-Bridge) ![License](https://img.shields.io/github/license/Bxel07/Xel-Psr7-Bridge) 

## Getting Started

To get start with this library you need several prerequest

### Prerequisites



* ext-swoole => V 5.0.0
* php => V 8.2






### Installation

1. Install with this command :


```sh
     composer require xel/psr7bridge
```


## Usage

1. In you server.php or in file which containt swoole server :

```sh
     <?php

use HttpSoft\Message\ServerRequestFactory;
use HttpSoft\Message\StreamFactory;
use HttpSoft\Message\UploadedFileFactory;
use HttpSoft\Message\ResponseFactory;
use Swoole\Http\Server;
use Swoole\Http\Request as SwooleRequest;
use Swoole\Http\Response as SwooleResponse;
use Xel\Psr7bridge\PsrFactory;


require __DIR__."/vendor/autoload.php";

$server = new Server("0.0.0.0", 9501, SWOOLE_PROCESS, SWOOLE_SOCK_TCP);

$server->set([
    "worker_num" => 35,
    "dispatch_mode" => 1
]);

/***
 * In this sample using Psr7 and Psr17 Provided by HttpSoft.
 * u can u other library to utilize this
 */
$psr7RequestFactory =  new PsrFactory
(
    new ServerRequestFactory(),
    new StreamFactory(),
    new UploadedFileFactory(),

);

$psr7Response = new ResponseFactory();
$psr7Stream =  new StreamFactory();

$server->on("request" , function (SwooleRequest $request, SwooleResponse $response) use ($psr7RequestFactory,$psr7Response, $psr7Stream){

    // ? Connect Swoole http request with Psr 17 factory
    $psr7RequestFactory->connectRequest($request);

    // ? Sample data in stream
    $data = $psr7Stream->createStream("Hello Swoole");
    
    // ? create response and
    $manage = $psr7Response->createResponse();
    $manage =  $manage->withBody($data);
    $manage = $manage->withStatus(200);
    
    // ?  bridge it to psr7
    $psr7RequestFactory->connectResponse($manage, $response);
});

$server->start();
```

_For more examples, please refer to the [Documentation](https://example.com)_

