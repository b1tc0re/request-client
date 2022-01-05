<?php namespace DeftCMS\Components\b1tc0re\Request;

use GuzzleHttp\Exception\GuzzleException;

defined('BASEPATH') || exit('No direct script access allowed');

/**
 * DeftCMS      Request Client http request
 *
 * @package	    DeftCMS
 * @category	Libraries
 * @author	    b1tc0re
 * @copyright   (c) 2017-2022, DeftCMS (http://deftcms.ru/)
 * @since	    Version 0.0.9a
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
     * @throws GuzzleException
     */
    protected function getServiceResponse($resource, $method = 'GET', $params = [])
    {
        $options = [];

        if( strtolower($method) === 'post' ) {
            $options['form_params'] = $params;
        }
        elseif( strtolower($method) === 'get' && count($params) ) {
            $resource .= '?' . $this->buildQueryString($params);
        }

        $resource = $this->getServiceUrl($resource);
        $response = $this->sendRequest($method, $resource, $options);

        return $this->getDecodedBody($response->getBody()->getContents());
    }
}