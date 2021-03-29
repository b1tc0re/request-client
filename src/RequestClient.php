<?php namespace DeftCMS\Components\b1tc0re\Request;

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
        elseif( strtolower($method) == 'get' && count($params) ) {
            $resource .= '?' . $this->buildQueryString($params);
        }

        $resource = $this->getServiceUrl($resource);
        $response = $this->sendRequest($method, $resource, $options);
        $decodedResponseBody = $this->getDecodedBody($response->getBody()->getContents());
        return $decodedResponseBody;
    }
}