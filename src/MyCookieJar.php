<?php namespace DeftCMS\Components\b1tc0re\Request;

use DateTime;
use DateTimeZone;
use Exception;
use GuzzleHttp\Cookie\CookieJar;
use HTTP_Request2;
use HTTP_Request2_CookieJar;
use HTTP_Request2_Exception;
use HTTP_Request2_LogicException;
use HTTP_Request2_MessageException;
use Net_URL2;

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
class MyCookieJar extends HTTP_Request2_CookieJar
{
    /**
     * Checks cookie array for correctness, possibly updating its 'domain', 'path' and 'expires' fields
     *
     * The checks are as follows:
     *   - cookie array should contain 'name' and 'value' fields;
     *   - name and value should not contain disallowed symbols;
     *   - 'expires' should be either empty parseable by DateTime;
     *   - 'domain' and 'path' should be either not empty or an URL where
     *     cookie was set should be provided.
     *   - if $setter is provided, then document at that URL should be allowed
     *     to set a cookie for that 'domain'. If $setter is not provided,
     *     then no domain checks will be made.
     *
     * 'expires' field will be converted to ISO8601 format from COOKIE format,
     * 'domain' and 'path' will be set from setter URL if empty.
     *
     * @param array    $cookie cookie data, as returned by
     *                         {@link HTTP_Request2_Response::getCookies()}
     * @param Net_URL2 $setter URL of the document that sent Set-Cookie header
     *
     * @return array    Updated cookie array
     * @throws HTTP_Request2_LogicException
     * @throws HTTP_Request2_MessageException
     */
    protected function checkAndUpdateFields(array $cookie, Net_URL2 $setter = null)
    {
        if ($missing = array_diff(['name', 'value'], array_keys($cookie))) {
            throw new HTTP_Request2_LogicException(
                "Cookie array should contain 'name' and 'value' fields",
                HTTP_Request2_Exception::MISSING_VALUE
            );
        }
        if (preg_match(HTTP_Request2::REGEXP_INVALID_COOKIE, $cookie['name'])) {
            throw new HTTP_Request2_LogicException(
                "Invalid cookie name: '{$cookie['name']}'",
                HTTP_Request2_Exception::INVALID_ARGUMENT
            );
        }
        if (preg_match(HTTP_Request2::REGEXP_INVALID_COOKIE, $cookie['value'])) {
            throw new HTTP_Request2_LogicException(
                "Invalid cookie value: '{$cookie['value']}'",
                HTTP_Request2_Exception::INVALID_ARGUMENT
            );
        }
        $cookie += ['domain' => '', 'path' => '', 'expires' => null, 'secure' => false];

        // Need ISO-8601 date @ UTC timezone
        if (!empty($cookie['expires'])
            && !preg_match('/^\\d{4}-\\d{2}-\\d{2}T\\d{2}:\\d{2}:\\d{2}\\+0000$/', $cookie['expires'])
        ) {
            try
            {
                $cookie['expires'] = is_numeric($cookie['expires']) ? (int) $cookie['expires'] : strtotime($cookie['expires']);
                $dt = new DateTime();

                $dt->setTimestamp($cookie['expires']);
                $dt->setTimezone(new DateTimeZone('UTC'));
                $cookie['expires'] = $dt->format(DateTime::ISO8601);
            } catch (Exception $e) {
                throw new HTTP_Request2_LogicException($e->getMessage());
            }
        }

        if (empty($cookie['domain']) || empty($cookie['path'])) {
            if (!$setter) {
                throw new HTTP_Request2_LogicException(
                    'Cookie misses domain and/or path component, cookie setter URL needed',
                    HTTP_Request2_Exception::MISSING_VALUE
                );
            }
            if (empty($cookie['domain'])) {
                if ($host = $setter->getHost()) {
                    $cookie['domain'] = $host;
                } else {
                    throw new HTTP_Request2_LogicException(
                        'Setter URL does not contain host part, can\'t set cookie domain',
                        HTTP_Request2_Exception::MISSING_VALUE
                    );
                }
            }
            if (empty($cookie['path'])) {
                $path = $setter->getPath();
                $cookie['path'] = empty($path)? '/': substr($path, 0, strrpos($path, '/') + 1);
            }
        }

        if ($setter && !$this->domainMatch($setter->getHost(), $cookie['domain'])) {
            throw new HTTP_Request2_MessageException(
                "Domain " . $setter->getHost() . " cannot set cookies for "
                . $cookie['domain']
            );
        }

        return $cookie;
    }
}