<?php namespace DeftCMS\Components\b1tc0re\Request;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Cookie\CookieJarInterface;
use GuzzleHttp\Cookie\FileCookieJar;
use GuzzleHttp\Cookie\SetCookie;
use GuzzleHttp\RequestOptions;
use HTTP_Request2;
use HTTP_Request2_CookieJar;
use HTTP_Request2_Exception;
use HTTP_Request2_Response;

defined('BASEPATH') || exit('No direct script access allowed');

/**
 * Request client factory
 *
 * @package	    DeftCMS
 * @category	Libraries
 * @author	    b1tc0re
 * @copyright   (c) 2022, DeftCMS (http://deftcms.ru/)
 * @since	    Version 0.0.9
 */
abstract class ServiceClient
{
    /**
     * Request schemes constants
     */
    const HTTPS_SCHEME = 'https';
    const HTTP_SCHEME = 'http';

    /**
     * Guzzle http client
     * @var Client
     */
    protected $guzzleClient;

    /**
     * HTTP2 Client
     * @var HTTP_Request2
     */
    protected $http2Client;

    /**
     * Request scheme
     * @var string
     */
    protected $serviceScheme = self::HTTPS_SCHEME;

    /**
     * HTTP domain
     * @var string
     */
    protected $serviceDomain = '';

    /**
     * Proxy address 127.0.0.1:8080
     * @var bool|string
     */
    protected $proxyAddress = false;

    /**
     * Cookie container
     * @var bool
     */
    protected $cookieJar = false;

    /**
     * Library name
     * @var string
     */
    protected $libraryName = 'DeftCMS';

    /**
     * Cookies container
     * @var CookieJarInterface
     */
    private $guzzleCookies;


    /**
     * Cookies container
     * @var MyCookieJar
     */
    private $request2Cookies;

    /**
     * Set proxy address
     *
     * @param string|false $proxy proxy address format: 127.0.0.1:8080 or false if not need proxy
     *
     * @return self
     */
    public function setProxyAddress($proxy = false)
    {
        $this->proxyAddress = $proxy;
        return $this;
    }

    /**
     * Return proxy address
     * @return bool|string
     */
    public function getProxyAddress()
    {
        return $this->proxyAddress;
    }

    /**
     * Set http domain
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
     * Return service domain
     * @return string
     */
    public function getServiceDomain()
    {
        return rtrim($this->serviceDomain, '/');
    }

    /**
     * Set use cookie jar
     * @param string|bool $cookieJar if set string use path to storage /tmp/cookie/
     * @return $this
     */
    public function setCookieJar($cookieJar)
    {
        $this->cookieJar = $cookieJar;
        return $this;
    }

    /**
     * Cookie jar
     * @return bool|string
     */
    public function getCookieJar()
    {
        return $this->cookieJar;
    }

    /**
     * Build user agent
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
     * Build service url
     * @param string $resource
     * @return string
     */
    protected function getServiceUrl($resource = '')
    {
        return $this->serviceScheme . '://' . $this->serviceDomain . '/' . $resource;
    }


    /**
     * Get http client
     * @param array $options Guzzle configure options
     * @return Client
     */
    public function getGuzzleClient($options = [])
    {
        if (is_null($this->guzzleClient))
        {
            $defaultOptions = [
                'base_uri' => $this->getServiceUrl(),
                RequestOptions::HEADERS => [
                    'Host'          => $this->getServiceDomain(),
                    'User-Agent'    => $this->getUserAgent(),
                    'Accept'        => '*/*',
                    'Accept-Encoding'   => 'gzip, deflate'
                ],
                RequestOptions::ALLOW_REDIRECTS => true
            ];

            if ( $this->getProxyAddress() ) {
                $defaultOptions['proxy'] = $this->getProxyAddress();
            }

            if( $this->getCookieJar() === true ) {
                $defaultOptions[RequestOptions::COOKIES] = $this->guzzleCookies = new CookieJar();
            }

            if( is_string($this->getCookieJar()) && is_dir($this->getCookieJar()) ) {
                $defaultOptions[RequestOptions::COOKIES] = $this->guzzleCookies = $this->getGuzzleFileCookie();
            }

            $this->guzzleClient = new Client(array_merge($defaultOptions, $options));
        }

        return $this->guzzleClient;
    }

    /**
     * Get http client
     * @param array $options Guzzle configure options
     * @return HTTP_Request2
     */
    public function getHttp2Client($options = [])
    {
        if( is_null($this->http2Client) )
        {
            $this->http2Client = new HTTP_Request2();

            $defaultOptions = [
                'follow_redirects'  => true,
                'strict_redirects'  => true,
                'max_redirects'     => 5
            ];

            $headers = [
                //'Host'          => $this->getServiceDomain(),
                'User-Agent'    => $this->getUserAgent(),
                'Accept'            => '*/*',
                'Accept-Encoding'   => 'gzip, deflate'
            ];

            try
            {
                if( array_key_exists('headers', $options) ) {
                    $headers = array_merge($headers, $options['headers']);
                    unset($options['headers']);
                }

                $this->http2Client->setHeader($headers);
            }
            catch (\HTTP_Request2_LogicException $e) {}

            if ( $this->getProxyAddress() ) {
                $defaultOptions['proxy'] = $this->getProxyAddress();
            }

            try
            {
                $this->http2Client->setConfig(array_merge($defaultOptions, $options));
            }
            catch (\HTTP_Request2_LogicException $e) {}

            if( $this->getCookieJar() === true ) {
                try
                {
                    $this->http2Client->setCookieJar(true);
                }
                catch (\HTTP_Request2_LogicException $e) {}
            }
        }

        return $this->http2Client;
    }

    /**
     * @param $method
     * @param $resource
     * @param array $options
     * @return array[
     * response => \Psr\Http\Message\ResponseInterface
     * ]
     * @throws \GuzzleHttp\Exception\GuzzleException
     */

    /**
     * @param $method
     * @param $resource
     * @param array $options
     * @return mixed|\Psr\Http\Message\ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function sendGuzzle($method, $resource, $options = [])
    {
        return $this->getGuzzleClient()->request($method, $this->getServiceUrl($resource), $options);
    }

    /**
     * Send request with http2 library
     * @param string $method    request method
     * @param string $resource
     * @param array $options
     * @return HTTP_Request2_Response
     *
     * @throws HTTP_Request2_Exception
     */
    protected function sendHttp2($method, $resource, $options = [])
    {
        $client = $this->getHttp2Client()
            ->setMethod($method)
            ->setUrl($this->getServiceUrl($resource));

        if( array_key_exists(RequestOptions::FORM_PARAMS, $options) ) {
            $client->setMethod(HTTP_Request2::METHOD_POST);
            $client->addPostParameter($options[RequestOptions::FORM_PARAMS]);
            unset($options[RequestOptions::FORM_PARAMS]);
        }

        if( array_key_exists(RequestOptions::JSON, $options) ) {
            $client->setHeader('Content-Type', 'application/json');
            $client->setBody(json_encode($options[RequestOptions::JSON]));
            unset($options[RequestOptions::JSON]);
        }

        if( array_key_exists(RequestOptions::HEADERS, $options) )
        {
            $client->setHeader($options[RequestOptions::HEADERS]);
            unset($options[RequestOptions::HEADERS]);
        }

        $client->setConfig($options);

        if( $this->getCookieJar() !== false ) {
            $client->setCookieJar($this->getCookieJarHttp2());
        }

        $response = $client->send();

        if( $this->getCookieJar() !== false ) {
            $this->storeCookie($client->getCookieJar());
        }

        return $response;
    }

    /**
     * Return cookie
     * @return HTTP_Request2_CookieJar
     */
    protected function getCookieJarHttp2()
    {
        $this->request2Cookies = new MyCookieJar();

        if( is_string($this->getCookieJar()) && is_dir($this->getCookieJar()) )
        {
           $guzzle = $this->getGuzzleFileCookie();

           foreach ($guzzle->toArray() as $cookie)
           {
               try
               {
                   $this->request2Cookies->store([
                       'name' => $cookie['Name'],
                       'expires' => $cookie['Expires'],
                       'domain' => $cookie['Domain'],
                       'path' => $cookie['Path'],
                       'secure' => $cookie['Secure'],
                       'value' => $cookie['Value'],
                   ]);
               }
               catch (HTTP_Request2_Exception $e) {}
           }
        }

        return $this->request2Cookies;
    }

    /**
     * Store cookie to file if need
     * @param MyCookieJar|HTTP_Request2_CookieJar $cookies
     */
    protected function storeCookie($cookies)
    {
        $this->request2Cookies = $cookies;

        if( is_string($this->getCookieJar()) && is_dir($this->getCookieJar()) )
        {
            $guzzle = $this->getGuzzleFileCookie();

            foreach ($this->request2Cookies->getAll() as $cookie)
            {
                $guzzle->setCookie(new SetCookie([
                    'Name'      => $cookie['name'],
                    'Expires'   => $cookie['expires'],
                    'Domain'    => $cookie['domain'],
                    'Path'      => $cookie['path'],
                    'Secure'    => $cookie['secure'],
                    'Value'     => $cookie['value'],
                ]));
            }
        }

    }

    /**
     * Return configured cookie
     * @return FileCookieJar
     */
    protected function getGuzzleFileCookie()
    {
       return new FileCookieJar(fn_path_join( $this->cookieJar, $this->getServiceDomain() . '.cook'), true);
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