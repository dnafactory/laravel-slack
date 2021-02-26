<?php

namespace DNAFactory\Slack\Support;

use DNAFactory\Slack\Exceptions\ConnectionException;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;

class Proxy
{
    const ENCODING_QUERY = 'query';
    const ENCODING_FORM_URLENCODE = 'form-urlencode';
    const ENCODING_JSON = 'json';

    protected HttpClient $httpClient;
    protected array $headers = [];
    protected string $baseUrl;
    protected int $waitMargin = 2;
    protected $maximumTries = 5;
    protected $defaultWait = 30;

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
        return json_decode($data, true);
    }

    protected function queryCall(
        string $endpoint,
        array $params = [],
        $method = 'GET',
        $encoding = self::ENCODING_QUERY
    ) {
        $rawData = $this->rawCall($endpoint, $params, $method, $encoding);
        parse_str($rawData, $data);
        return $data;
    }

    protected function rawCall(string $endpoint, array $params, string $method, $encoding)
    {
        $httpParams = ['headers' => $this->headers];
        if ($encoding == self::ENCODING_JSON) {
            $httpParams['body'] = json_encode($params);
            $httpParams['headers']['Content-Type'] = 'application/json';
        } elseif ($encoding == self::ENCODING_FORM_URLENCODE) {
            $httpParams['body'] = http_build_query($params);
            $httpParams['headers']['Content-Type'] = 'application/x-www-form-urlencoded';
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
            try{
                $response = $this->httpClient->request($method, $uri, $params);
            } catch (RequestException $e) {
                $this->waitRateLimit($e->hasResponse()
                    ? $e->getResponse()
                    : null);
                continue;
            }
            if ($response->getStatusCode() == 200) {
                return (string)$response->getBody();
            }
        }
        throw new ConnectionException("Call to $method failed after {$i} attempts.");
    }

    protected function waitRateLimit(?ResponseInterface $response)
    {
        $wait = $this->defaultWait;
        if (!is_null($response)) {
            $wait = $response->getHeader('Retry-After')[0] ?? '';
            $wait = (int)$wait;
        }
        time_sleep_until(time() + $wait + $this->waitMargin);
    }
}
