<?php namespace DeftCMS\Components\b1tc0re\Request;

use DeftCMS\Engine;

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
class RequestClient2 extends ServiceClient
{

    protected $serviceDomain = "dzen.ru";

    public function __construct()
    {
        $this->setCookieJar(fn_path_join(Engine::$DT->config->item('storage_path'), 'cookies'));
        $this->setProxyAddress('http://192.168.0.51:8080');
    }

    public function sendRequest()
    {
        $r = $this->sendHttp2('GET', '', [
            'ssl_verify_host' => false,
            'ssl_verify_peer' => false,
        ]);

        /*$r = $this->sendGuzzle('GET', '', [
            'verify' => false
        ]);*/

        //print_r($r);
        //exit;

    }
}
