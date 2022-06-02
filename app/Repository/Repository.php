<?php
/**
 * Created by PhpStorm.
 * User: cyrillbolliger
 * Date: 07.10.18
 * Time: 17:07
 */

namespace App\Repository;


use Webling\API\Client;
use Webling\API\ClientException;

abstract class Repository
{
    /**
     * Timeout for requests to webling in seconds.
     *
     * Yes, it must be super high, we had issues with lower limits.
     */
    private const TIMEOUT = 300;
    private const CONNECTTIMEOUT = 4;
    
    /**
     * The api key
     *
     * Exposed to child classes, so they can instantiate further repositories.
     *
     * @var string
     */
    protected $api_key;
    /**
     * The api url
     *
     * Exposed to child classes, so they can instantiate further repositories.
     *
     * @var string
     */
    protected $api_url;
    /**
     * The Webling client object
     *
     * @see https://github.com/usystems/webling-api-php
     *
     * @var Client
     */
    private $webling_client;
    
    /**
     * Repository constructor.
     *
     * @param string $api_key
     * @param string|null $api_url your webling address, e.g: "demo.webling.ch"
     *                             the one set in the .env file will be used if
     *                             parameter is left out.
     *
     * @throws \Webling\API\ClientException
     */
    public function __construct(string $api_key, string $api_url = null)
    {
        if (!$api_url) {
            $api_url = config('app.webling_base_url');
        }
        
        $this->api_key = $api_key;
        $this->api_url = $api_url;
        
        $curlOptions = [
            'timeout' => self::TIMEOUT,
            'connecttimeout' => self::CONNECTTIMEOUT
        ];
        
        $this->webling_client = new Client($api_url, $api_key, $curlOptions);
    }
    
    /**
     * Query Webling
     *
     * @link https://gruenesandbox.webling.ch/api for more details.
     *
     * @param string $endpoint this must not be encoded.
     *
     * @return \Webling\API\IResponse|\Webling\API\Response
     * @throws \Webling\API\ClientException
     */
    protected function apiGet(string $endpoint)
    {
        return $this->apiSendWithRetry('get', $this->prepareEndpoint($endpoint));
    }
    
    /**
     * Wrapper that automatically retries if request fails.
     *
     * Do only use it on idempotent API methods.
     *
     * @param string $method
     * @param string $url
     * @param $payload
     * @param int $tries
     * @param int $attempt
     *
     * @return \Webling\API\IResponse|\Webling\API\Response
     * @throws ClientException
     */
    private function apiSendWithRetry(string $method, string $url, $payload = null, int $tries = 3, int $attempt = 1)
    {
        try {
            return $this->webling_client->$method($url, $payload);
        } catch (ClientException $exception) {
            if ($attempt < $tries) {
                $attempt++;
                sleep(1); // don't retry immediately (rate limits)
                return $this->apiSendWithRetry($method, $url, $payload, $tries, $attempt);
            } else {
                throw $exception;
            }
        }
    }
    
    /**
     * Preprocess the endpoint url: Strip api key, url encode query args
     *
     * @param string $endpoint
     *
     * @return string
     */
    private function prepareEndpoint(string $endpoint)
    {
        $endpoint = $this->removeApiKey($endpoint);
        $encoded = $this->urlEncodeEndpoint($endpoint);
        
        return $encoded;
    }
    
    /**
     * Make sure we never send the api key as url parameter
     *
     * @param string $endpoint
     *
     * @return null|string
     */
    private function removeApiKey(string $endpoint)
    {
        /** @noinspection SpellCheckingInspection */
        return preg_replace('/&apikey=[^&]*/', '', $endpoint);
    }
    
    /**
     * Encode the url to avoid special chars
     *
     * @param string $endpoint
     *
     * @return string
     */
    private function urlEncodeEndpoint(string $endpoint)
    {
        $query_start = strpos($endpoint, '?');
        if (!$query_start) {
            return $endpoint;
        }
        
        $query = substr($endpoint, $query_start + 1);
        $base = substr($endpoint, 0, $query_start);
        
        $webling_ready = $this->encodeQueryString($query);
        
        return "$base?$webling_ready";
    }
    
    /**
     * Encode the quoted parts of the query string as well as the whitespaces.
     *
     * This method seems odd, but since the query syntax of webling may contain
     * reserved characters in query arguments, we have to encode this step by
     * step.
     *
     * @param string $query
     * @return string
     */
    private function encodeQueryString($query)
    {
        // query arguments may contain ampersands in the quoted parts,
        // so encode them first
        $quotes = ["'", '"', '`'];
        foreach ($quotes as $char) {
            $query = $this->encodeQuoted($query, $char);
        }
        
        // then, we can split up the query
        $arguments = explode('&', $query);
        
        // and encode the query argument values
        foreach ($arguments as $index => $argument) {
            $separator = strpos($argument, '=');
            $key = substr($argument, 0, $separator);
            $value = substr($argument, $separator + 1);
            $encoded = urlencode(urldecode($value));
            $arguments[$index] = "$key=$encoded";
        }
        
        return implode('&', $arguments);
    }
    
    /**
     * Url-encode the parts that are in between the $quoteChars
     *
     * @param string $string
     * @param string $quoteChar
     * @return string
     */
    private function encodeQuoted($string, $quoteChar)
    {
        $firstQuote = strpos($string, $quoteChar);
        
        if (!$firstQuote) {
            return $string;
        }
        
        $tokens = explode($quoteChar, $string);
        $quoteEvens = $firstQuote === 0;
        
        foreach ($tokens as $index => $token) {
            $even = 0 === $index % 2;
            if (($quoteEvens && $even)
                || (!$quoteEvens && !$even)) {
                $tokens[$index] = urlencode($token);
            }
        }
        
        return implode($quoteChar, $tokens);
    }
    
    /**
     * Update record in Webling
     *
     * @link https://gruenesandbox.webling.ch/api for more details.
     *
     * @param string $endpoint this must not be encoded.
     * @param array $data only provide the fields to update. empty fields will be emptied.
     *
     * @return \Webling\API\IResponse|\Webling\API\Response
     * @throws \Webling\API\ClientException
     */
    protected function apiPut(string $endpoint, array $data)
    {
        // todo: implement history
        return $this->apiSendWithRetry('put', $this->prepareEndpoint($endpoint), $data);
    }
    
    /**
     * Insert record into Webling
     *
     * @link https://gruenesandbox.webling.ch/api for more details.
     *
     * @param string $endpoint this must not be encoded.
     * @param array $data
     *
     * @return \Webling\API\IResponse|\Webling\API\Response
     * @throws \Webling\API\ClientException
     */
    protected function apiPost(string $endpoint, array $data)
    {
        // todo: implement history
        return $this->webling_client->post($this->prepareEndpoint($endpoint), $data);
    }
    
    /**
     * Delete record in Webling
     *
     * @link https://gruenesandbox.webling.ch/api for more details.
     *
     * @param string $endpoint this must not be encoded.
     *
     * @return \Webling\API\IResponse|\Webling\API\Response
     * @throws \Webling\API\ClientException
     */
    protected function apiDelete(string $endpoint)
    {
        // todo: implement history
        return $this->webling_client->delete($this->prepareEndpoint($endpoint));
    }
}
