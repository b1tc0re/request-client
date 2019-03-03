<?php namespace DeftCMS\Components\b1tc0re\Request;


use DeftCMS\Engine;

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * DeftCMS      Request Client http request
 *
 * @package	    DeftCMS
 * @category	Libraries
 * @author	    b1tc0re
 * @copyright   (c) 2017-2019, DeftCMS (http://deftcms.org)
 * @since	    Version 0.0.2
 */
class RequestClient extends AbstractServiceClient
{
    /**
     * Returns API service response.
     *
     * @param  string $resource
     * @param  string $method
     * @param  array $params
     * @return array
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function getServiceResponse($resource, $method = 'GET', $params = [])
    {
        $options = [];

        if( strtolower($method) == 'post' ) {
            $options['form_params'] = $params;
        }

        $resource = $this->getServiceUrl($resource);
        $response = $this->sendRequest($method, $resource, $options);
        $decodedResponseBody = $this->getDecodedBody($response->getBody()->getContents());
        return $decodedResponseBody;
    }

    /**
     * Cache wrapper get
     * @param string $id
     * @return mixed
     */
    protected function cacheGet($id)
    {
        static $cache;
        $cache = $cache ?? Engine::$DT->cache->initialize([
                'adapter'    => 'file',
                'backup'     => 'dummy',
                'key_prefix' => 'http:',
        ]);

        return $cache->get($id);
    }

    /**
     * Cache wrapper save
     * @param	string	$id	    Cache ID
     * @param	mixed	$data	Data to store
     * @param	int	    $ttl	Cache TTL (in seconds)
     * @param	bool	$raw	Whether to store the raw value
     * @return	bool	TRUE on success, FALSE on failure
     */
    protected function cacheSave($id, $data, $ttl = 60, $raw = FALSE)
    {
        static $cache;
        $cache = $cache ?? Engine::$DT->cache->initialize([
                'adapter'    => 'file',
                'backup'     => 'dummy',
                'key_prefix' => 'http:',
        ]);

        return $cache->save($id, $data, $ttl, $raw);
    }
}