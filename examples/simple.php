<?php

// Simple usage

require_once '../vendor/autoload.php';


$loop = \React\EventLoop\Factory::create();

$booster = new \MM\React\Varnish\Booster($loop, '127.0.0.1', '80');

$socket = new \React\Socket\Server('8080', $loop);
$http = new \React\Http\Server($socket, function (\Psr\Http\Message\ServerRequestInterface $request) use ($booster) {

    return $booster->process($request, new \Zend\Stratigility\Delegate\CallableDelegateDecorator(function ($req, $resp) {
        return $resp;
    }, new \React\Http\Response()));

});


$loop->run();