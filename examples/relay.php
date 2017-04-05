<?php

// Usage with Relay (https://github.com/relayphp/Relay.Relay) library

require_once '../vendor/autoload.php';


$loop = \React\EventLoop\Factory::create();

$relayBuilder = new Relay\RelayBuilder();

$relay = $relayBuilder->newInstance([
    new \MM\React\Varnish\Booster($loop, '127.0.0.1', '80')
]);

$socket = new \React\Socket\Server('8080', $loop);
$http = new \React\Http\Server($socket, function(\Psr\Http\Message\ServerRequestInterface $request) use ($relay) {

    return $relay->__invoke($request, new \React\Http\Response());

});


$loop->run();