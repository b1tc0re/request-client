<?php namespace DeftCMS\Components\b1tc0re\Request;

defined('BASEPATH') || exit('No direct script access allowed');

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;

/**
 * DeftCMS      AbstractServiceClient
 *
 * @package	    DeftCMS
 * @category	Libraries
 * @author	    b1tc0re
 * @copyright   (c) 2017-2022, DeftCMS (http://deftcms.ru/)
 * @since	    Version 0.0.9a
 */
abstract class AbstractServiceClient
{
    /**
     * Request schemes constants
     */
    const HTTPS_SCHEME = 'https';
    const HTTP_SCHEME = 'http';

    /**
     * Decode type json to object
     */
    const DECODE_TYPE_JSON_OBJECT  = 'object';

    /**
     * Decode type json to array
     */
    const DECODE_TYPE_JSON_ARRAY  = 'array';

    /**
     * Decode type json to xml
     */
    const DECODE_TYPE_XML   = 'xml';

    /**
     * Decode type json to html
     */
    const DECODE_TYPE_HTML  = 'html';

    /**
     * Default decode response type
     */
    const DECODE_TYPE_DEFAULT = self::DECODE_TYPE_JSON_ARRAY;

    /**
     * @var string
     */
    protected $serviceScheme = self::HTTPS_SCHEME;

    /**
     * Can be HTTP 1.0 or HTTP 1.1
     * @var string
     */
    protected $serviceProtocolVersion = '1.1';

    /**
     * @var string
     */
    protected $serviceDomain = '';

    /**
     * @var string
     */
    protected $servicePort = '';

    /**
     * @var string
     */
    protected $proxy = '';

    /**
     * @var bool
     */
    protected $debug = false;

    /**
     * @var null|Client
     */
    protected $client = null;

    /**
     * @var string
     */
    protected $libraryName = 'DeftCMS';

    /**
     * @return string
     */
    public function getUserAgent()
    {
        $version = '0.0.9';

        if( class_exists('DeftCMS\Engine', false) ) {
            $version = \DeftCMS\Engine::DT_VERSION;
        }

        return $this->libraryName . '/' . $version;
    }

    /**
     * @param $proxy
     * @return $this
     */
    public function setProxy($proxy)
    {
        $this->proxy = $proxy;

        return $this;
    }

    /**
     * @return string
     */
    public function getProxy()
    {
        return $this->proxy;
    }

    /**
     * @param $debug
     * @return $this
     */
    public function setDebug($debug)
    {
        $this->debug = (bool) $debug;

        return $this;
    }

    /**
     * @return bool
     */
    public function getDebug()
    {
        return $this->debug;
    }

    /**
     * @param string $serviceDomain
     *
     * @return self
     */
    public function setServiceDomain($serviceDomain)
    {
        $this->serviceDomain = $serviceDomain;

        return $this;
    }

    /**
     * @return string
     */
    public function getServiceDomain()
    {
        return $this->serviceDomain;
    }

    /**
     * @param string $servicePort
     *
     * @return self
     */
    public function setServicePort($servicePort)
    {
        $this->servicePort = $servicePort;

        return $this;
    }

    /**
     * @return string
     */
    public function getServicePort()
    {
        return $this->servicePort;
    }

    /**
     * @param string $serviceScheme
     *
     * @return self
     */
    public function setServiceScheme($serviceScheme = self::HTTPS_SCHEME)
    {
        $this->serviceScheme = $serviceScheme;

        return $this;
    }

    /**
     * @return string
     */
    public function getServiceScheme()
    {
        return $this->serviceScheme;
    }

    /**
     * @param string $resource
     * @return string
     */
    protected function getServiceUrl($resource = '')
    {
        return $this->serviceScheme . '://' . $this->serviceDomain . '/' . $resource;
    }


    /**
     * Set client
     *
     * @param ClientInterface $client
     * @return $this
     */
    protected function setClient(ClientInterface $client)
    {
        $this->client = $client;
        return $this;
    }

    /**
     * Return http client
     * @return ClientInterface
     */
    protected function getClient()
    {
        if (is_null($this->client))
        {
            $defaultOptions = [
                'base_uri' => $this->getServiceUrl(),
                'headers' => [
                    'Host'          => $this->getServiceDomain(),
                    'User-Agent'    => $this->getUserAgent(),
                    'Accept'        => '*/*'
                ]
            ];

            if ($this->getProxy())
            {
                $defaultOptions['proxy'] = $this->getProxy();
            }

            if ($this->getDebug())
            {
                $defaultOptions['debug'] = $this->getDebug();
            }

            $this->client = new Client($defaultOptions);
        }

        return $this->client;
    }

    /**
     * Sends a request
     *
     * @param string              $method  HTTP method
     * @param string $uri         URI object or string.
     * @param array               $options Request options to apply.
     *
     * @return \Psr\Http\Message\ResponseInterface
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function sendRequest($method, $uri, array $options = [])
    {
        try
        {
            return $this->getClient()->request($method, $uri, $options);
        }
        catch (ClientException $ex)
        {
            $response     = $ex->getResponse();
            $ResponseBody = 'body:empty';
            $code         = "code:empty";

            if( $response !== null )
            {
                $ResponseBody = $response->getBody()->getContents();
                $code         = $response->getStatusCode();
            }


            \DeftCMS\Engine::$Log->error('Service client error code '. $code . '; '.$ResponseBody);
            \DeftCMS\Engine::$Log->error('Service client error code '. $uri);
            throw $ex;
        }
    }

    /**
     * @param string $body
     * @param string $type
     * @return array|string|\SimpleXMLElement
     */
    protected function getDecodedBody($body, $type = null)
    {
        if (!isset($type)) {
            $type = static::DECODE_TYPE_DEFAULT;
        }

        switch ($type)
        {
            case self::DECODE_TYPE_XML:
                return simplexml_load_string((string) $body);
            case self::DECODE_TYPE_JSON_OBJECT:
                return json_decode((string) $body);
            case self::DECODE_TYPE_JSON_ARRAY:
                return json_decode((string) $body, true);
            default:
                return (string) $body;
        }
    }

    /**
     * Returns URL-encoded query string
     *
     * @note: similar to http_build_query(),
     * but transform key=>value where key == value to "?key" param.
     *
     * @param array        $queryData
     * @param string       $prefix
     * @param string       $argSeparator
     * @param int          $encType
     *
     * @return string $queryString
     */
    protected function buildQueryString($queryData, $prefix = '', $argSeparator = '&', $encType = PHP_QUERY_RFC3986)
    {
        foreach ($queryData as $k => &$v) {
            if (!is_scalar($v)) {
                $v = implode(',', $v);
            }
        }
        $queryString = http_build_query($queryData, $prefix, $argSeparator, $encType);
        foreach ($queryData as $k => $v) {
            if ($k==$v) {
                $queryString = str_replace("$k=$v", $v, $queryString);
            }
        }
        return $queryString;
    }
}