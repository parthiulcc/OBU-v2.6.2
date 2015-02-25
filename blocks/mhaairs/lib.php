<?php
/**
 * ZEND Web Services Plugin for block MHAAIRS
 *
 * @package    block
 * @subpackage mhaairs
 * @copyright  2013 Moodlerooms inc.
 * @author     Teresa Hardy <thardy@moodlerooms.com>
 */

defined('MOODLE_INTERNAL') || die();

/**
 *
 * @param string $linktype
 * @param bool $forcefetch
 * @return bool|array|string
 */
function block_mhaairs_getlinks($linktype, $forcefetch=false) {
    $customer_number = get_config('core', 'block_mhaairs_customer_number');
    if ($customer_number === false) { // Do we have number?
        return false;
    }

    $cachename = "block_mhaairs_cache{$linktype}";
    if (!$forcefetch) {
        $cached = get_config('core', $cachename);
        if ($cached !== false) {
            $result = unserialize($cached);
            return $result;
        }
    }

    static $zendready = false;
    if (!$zendready) {
        $dir = get_config('core', 'dirroot');
        // Use MR framework Zend if installed otherwise rewert to local copy of Zend.
        try {
            $bootstrapfile = "{$dir}/local/mr/bootstrap.php";
            if (file_exists($bootstrapfile)) {
                require($bootstrapfile);
                mr_bootstrap::zend();
                $zendready = true;
            }
        } catch (Exception $e) {
            // Silence any exception.
        }

        if (!$zendready) {
            set_include_path(get_include_path().PATH_SEPARATOR.$dir.'/blocks/mhaairs/lib');
            $zendready = true;
        }
    }

    // @codingStandardsIgnoreStart
    require_once('Zend/Json.php');
    require_once('Zend/Oauth/Consumer.php');
    require_once('Zend/Oauth/Client.php');
    // @codingStandardsIgnoreEnd

    $endpoint = 'GetHelpLinks';
    if ($linktype == 'services') {
        $endpoint = 'GetCustomerAvailableTools';
    }

    $baseurl = 'http://mhaairs.tegrity.com/v1/Config/';
    $url = $baseurl.$customer_number.'/'.$endpoint;

    $aconfig = array(
            'requestScheme'   => Zend_Oauth::REQUEST_SCHEME_QUERYSTRING,
            'requestMethod'   => Zend_Oauth::GET,
            'signatureMethod' => 'HMAC-SHA1',
            'consumerKey'     => 'SSOConfig',
            'consumerSecret'  => '3DC9C384'
    );

    $result_data = false;
    try {
        $tacc = new Zend_Oauth_Token_Access();
        $client = $tacc->getHttpClient($aconfig, $url);
        $client->setMethod(Zend_Oauth_Client::GET);
        $client->setEncType(Zend_Oauth_Client::ENC_URLENCODED);

        $response    = $client->request();
        $result_data = $response->getBody();

        // Get content type.
        $result_type = $response->getHeader(Zend_Oauth_Client::CONTENT_TYPE);

        // Is this Json encoded data?
        if (stripos($result_type, 'application/json') !== false) {
            $result_data = Zend_Json::decode($result_data);
        }

        // By default set the status to the HTTP response status.
        $status      = $response->getStatus();
        $description = $response->getMessage();
        if ($status != 200) {
            $result_data = false;
        }
    } catch (Exception $e) {
        $status      = (string)$e->getCode();
        $description = $e->getMessage();
    }

    $logmsg = $status . ": " . $description;
    add_to_log(SITEID, 'mhaairs', 'block mhaairs_getlinks', '', $logmsg);

    $tostore = serialize($result_data);
    set_config($cachename, $tostore);

    return $result_data;
}

