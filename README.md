# reactphp-varnish-esi-booster
Varnish Esi Booster is an asynchronous, PSR-7 and PSR-15 compatible middleware using ReactPHP.

It will prefetch all Your Esi blocks found in response body. This will speed up Esi blocks processing. Normally Varnish will process Your Esi blocks one by one, synchronously.

### Usage

You can find example usage of this library in `examples` directory. There are three examples:

- Simple usage
- with Relay library (https://github.com/relayphp/Relay.Relay)
- with Zend Stratigility library (https://github.com/zendframework/zend-stratigility)

To test examples You need to install dev requirements with composer.

### Esi blocks format
Booster will look for Esi blocks defined as follow:
```
<esi:include src="URL"/>
```