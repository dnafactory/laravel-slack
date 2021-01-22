<?php

namespace DNAFactory\Slack\Endpoints;

use DNAFactory\Slack\Exceptions\ConnectionException;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;

class Proxy
{
    const ENCODING_QUERY = 'query';
    const ENCODING_FORM_URLENCODE = 'form-urlencode';
    const ENCODING_JSON = 'json';

    protected HttpClient $httpClient;
    protected array $headers = [];
    protected string $baseUrl;
    protected int $waitMargin;
    protected $maximumTries = 5;

    public function __construct(HttpClient $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    public function setHeader(string $name, string $value)
    {
        $this->headers[$name] = $value;
        return $this;
    }

    public function setBaseUrl(string $baseUrl)
    {
        $this->baseUrl = $baseUrl;
        return $this;
    }

    public function setToken(string $token)
    {
        return $this->setHeader('Authorization', 'Bearer ' . $token);
    }

    public function setMaximumTries(int $tries)
    {
        $this->maximumTries = max($tries, 1);
        return $this;
    }

    public function setWaitMargin(int $waitMargin)
    {
        $this->waitMargin = $waitMargin;
        return $this;
    }

    protected function jsonCall(string $endpoint, array $params = [], $method = 'GET', $encoding = self::ENCODING_QUERY)
    {
        $data = $this->rawCall($endpoint, $params, $method, $encoding);
        return json_decode($data->getBody(), true);
    }

    protected function queryCall(
        string $endpoint,
        array $params = [],
        $method = 'GET',
        $encoding = self::ENCODING_QUERY
    ) {
        $rawData = $this->rawCall($endpoint, $params, $method, $encoding);
        parse_str($rawData->getBody(), $data);
        return $data;
    }

    protected function rawCall(string $endpoint, array $params, string $method, $encoding)
    {
        $httpParams = ['headers' => $this->headers];
        if ($encoding == self::ENCODING_JSON) {
            $httpParams['body'] = json_encode($params);
        } elseif ($encoding == self::ENCODING_FORM_URLENCODE) {
            $httpParams['body'] = http_build_query($params);
        } elseif ($encoding == self::ENCODING_QUERY) {
            $httpParams['query'] = http_build_query($params);
        }
        $uri = $this->baseUrl . $endpoint;
        try {
            return $this->rawRequest($method ?? 'GET', $uri, $httpParams);
        } catch (GuzzleException $e) {
            throw new ConnectionException($e->getMessage());
        }
    }

    /**
     * @param string $method
     * @param string $uri
     * @param array $params
     * @return ResponseInterface
     * @throws GuzzleException
     */
    protected function rawRequest(string $method, string $uri, array $params)
    {
        for ($i = 0; $i < $this->maximumTries; $i++) {
            $request = $this->httpClient->request($method, $uri, $params);
            if ($request->getStatusCode() != 429) { // too many requests
                break;
            }
            $retryAfter = (int)$request->getHeader('Retry-After');
            time_sleep_until(time() + $retryAfter + $this->waitMargin);
        }
        return $request ?? null;
    }
}
