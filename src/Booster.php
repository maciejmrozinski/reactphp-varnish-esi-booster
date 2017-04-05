<?php

namespace MM\React\Varnish;

use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use React\Dns\Resolver\Factory as DnsFactory;
use React\EventLoop\LoopInterface;
use React\HttpClient\Client;
use React\HttpClient\Factory as HttpFactory;
use Zend\Stratigility\Delegate\CallableDelegateDecorator;

class Booster implements MiddlewareInterface
{
    /**
     * @var string
     */
    private $varnishHost;

    /**
     * @var string
     */
    private $varnishPort;

    /**
     * @var LoopInterface
     */
    private $loop;

    /**
     * @var Client
     */
    private $client;

    /**
     * Booster constructor.
     * @param LoopInterface $loop
     * @param string $varnishHost
     * @param string $varnishPort
     */
    public function __construct(LoopInterface $loop, $varnishHost, $varnishPort)
    {
        $this->loop = $loop;
        $this->varnishHost = $varnishHost;
        $this->varnishPort = $varnishPort;

        if ($this->varnishHost) {
            $dnsResolverFactory = new DnsFactory();
            $dnsResolver = $dnsResolverFactory->createCached('8.8.8.8', $this->loop);

            $factory = new HttpFactory();
            $this->client = $factory->create($this->loop, $dnsResolver);
        }
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param callable $next
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        return $this->process($request, new CallableDelegateDecorator($next, $response));
    }

    /**
     * Process an incoming server request and return a response, optionally delegating
     * to the next middleware component to create the response.
     *
     * @param ServerRequestInterface $request
     * @param DelegateInterface $delegate
     *
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $response = $delegate->process($request);
        if ($this->varnishHost) {
            $response->getBody()->rewind();
            $matches = [];
            preg_match_all('/<esi:include src="([^"]+)"\/>/', $response->getBody()->getContents(), $matches);
            if (count($matches[1])) {

                $headers = [];
                foreach ($request->getHeaders() as $name => $arr) {
                    $headers[$name] = $request->getHeaderLine($name);
                }
                $headers = array_merge($headers, ['X-Varnish-Booster' => '1.0']);

                foreach ($matches[1] as $url) {
                    $url = preg_replace('/(https?:\/\/)[^\/]+(\/.*)/', sprintf('${1}%s:%s${2}', $this->varnishHost, $this->varnishPort), $url);
                    $this->client->request('GET', $url, $headers, $request->getProtocolVersion())->end();
                }
            }
        }
        return $response;
    }
}