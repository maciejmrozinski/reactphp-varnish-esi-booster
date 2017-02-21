# reactphp-varnish-esi-booster
Varnish Esi Booster is an asynchronous, PSR-7 compatible middleware using ReactPHP

It will prefetch all Your Esi blocks found in response body. This will speed up Esi blocks processing. Normally Varnish will process Your Esi blocks one by one, synchronously.

###Usage (using Relay)
```
$booster = new MM\React\Varnish\Booster($loop, '127.0.0.1', '80');

$relayBuilder = new Relay\RelayBuilder();

$relay = $relayBuilder->newInstance([
    $booster
]);

$relay->run($psr7Request, $psr7Response);

```

###Esi blocks format
Booster will look for Esi blocks defined as follow:
```
<esi:include src="URL"/>
```