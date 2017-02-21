<?php
/**
 * Created by PhpStorm.
 * User: Maciej Mroziński <maciej.k.mrozinski@gmail.com>
 * Date: 21.02.17
 * Time: 11:11
 */

namespace MM\React\Varnish;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use React\Dns\Resolver\Factory as DnsFactory;
use React\EventLoop\LoopInterface;
use React\HttpClient\Client;
use React\HttpClient\Factory as HttpFactory;

class Booster
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
        /** @var ResponseInterface $response */
        $response = $next($request, $response);
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