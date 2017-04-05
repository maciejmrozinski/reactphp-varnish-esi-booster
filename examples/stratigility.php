<?php

// Usage with Zend Stratigility (https://github.com/zendframework/zend-stratigility) library

require_once '../vendor/autoload.php';


$loop = \React\EventLoop\Factory::create();

$pipe = new \Zend\Stratigility\MiddlewarePipe();
$pipe->pipe('/', new \MM\React\Varnish\Booster($loop, '127.0.0.1', '80'));


$socket = new \React\Socket\Server('8080', $loop);
$http = new \React\Http\Server($socket, function(\Psr\Http\Message\ServerRequestInterface $request) use ($pipe) {

    return $pipe->__invoke($request, new \React\Http\Response(), new \Zend\Stratigility\NoopFinalHandler());

});


$loop->run();