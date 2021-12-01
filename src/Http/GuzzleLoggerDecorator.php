<?php

namespace Freyr\DP\Http;

use Freyr\DP\SimpleLogger;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Psr\Http\Message\RequestInterface;

class GuzzleLoggerDecorator implements ClientInterface
{


    public function __construct(private Client $client, private SimpleLogger $logger)
    {
    }

    public function __call(string $name, array $arguments)
    {
        $response = $this->client->__call($name, $arguments);
        $this->logger->log(['response'=>$response->getBody()]);
        return $response;
    }

    private function log(array $data)
    {
        $this->logger->log($data);
    }



    public function send(RequestInterface $request, array $options = [])
    {
        $result = $this->client->send($request, $options);
        $this->log(['response' => $result->getBody()->getContents()]);
        return $result;
    }

    public function sendAsync(RequestInterface $request, array $options = [])
    {
        $result = $this->client->sendAsync($request, $options);
        $this->log(['response' => 'async']);
        return $result;
    }

    public function request($method, $uri, array $options = [])
    {
        $result =  $this->client->request($method, $uri, $options);
        $this->log(['response' => $result->getBody()->getContents()]);
        return $result;
    }

    public function requestAsync($method, $uri, array $options = [])
    {
        $result =  $this->client->requestAsync($method, $uri, $options);
        $this->log(['response' => 'async']);
        return $result;
    }

    public function getConfig($option = null)
    {
        $result =  $this->client->getConfig($option);
        $this->log(['response' => $result]);
        return $result;
    }
}
