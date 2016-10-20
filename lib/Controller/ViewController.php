<?php
/**
 * OwnCloud - B2sharebridge App
 *
 * PHP Version 5-7
 *
 * @category  Owncloud
 * @package   B2shareBridge
 * @author    EUDAT <b2drop-devel@postit.csc.fi>
 * @copyright 2015 EUDAT
 * @license   AGPL3 https://github.com/EUDAT-B2DROP/b2sharebridge/blob/master/LICENSE
 * @link      https://github.com/EUDAT-B2DROP/b2sharebridge.git
 */

namespace OCA\B2shareBridge\Controller;

use OCA\B2shareBridge\Model\DepositStatusMapper;
use OCA\B2shareBridge\Model\StatusCodes;
use OCA\B2shareBridge\View\Navigation;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IConfig;
use OCP\IRequest;
use OCP\Util;

/**
 * Implement a ownCloud AppFramework Controller
 *
 * @category Owncloud
 * @package  B2shareBridge
 * @author   EUDAT <b2drop-devel@postit.csc.fi>
 * @license  AGPL3 https://github.com/EUDAT-B2DROP/b2sharebridge/blob/master/LICENSE
 * @link     https://github.com/EUDAT-B2DROP/b2sharebridge.git
 */
class ViewController extends Controller
{
    protected $userId;
    protected $statusCodes;
    protected $mapper;
    protected $navigation;

    /**
     * Creates the AppFramwork Controller
     *
     * @param string              $appName     name of the app
     * @param IRequest            $request     request object
     * @param IConfig             $config      config object
     * @param DepositStatusMapper $mapper      whatever
     * @param StatusCodes         $statusCodes whatever
     * @param string              $userId      userid
     * @param Navigation          $navigation  navigation bar object
     */
    public function __construct(
        $appName,
        IRequest $request,
        IConfig $config,
        DepositStatusMapper $mapper,
        StatusCodes $statusCodes,
        $userId,
        Navigation $navigation
    ) {
        parent::__construct($appName, $request);
        $this->userId = $userId;
        $this->mapper = $mapper;
        $this->statusCodes = $statusCodes;
        $this->config = $config;
        $this->navigation = $navigation;
    }

    /**
     * CAUTION: the @Stuff turns off security checks; for this page no admin is
     *          required and no CSRF check. If you don't know what CSRF is, read
     *          it up in the docs or you might create a security hole. This is
     *          basically the only required method to add this exemption, don't
     *          add it to any other method if you don't exactly know what it does
     *
     * @param string $filter filtering string
     *
     * @return TemplateResponse
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function depositList($filter = 'all')
    {

        Util::addStyle('b2sharebridge', 'style');
        Util::addStyle('files', 'files');

        $publications = [];
        if ($filter == 'all') {
            foreach (
                array_reverse(
                    $this->mapper->findAllForUser($this->userId)
                ) as $publication) {
                $publications[] = $publication;
            }
        } else {
            foreach (
                array_reverse(
                    $this->mapper->findAllForUserAndStateString(
                        $this->userId,
                        $filter
                    )
                ) as $publication) {
                $publications[] = $publication;
            }
        }

        $params = [
            'user' => $this->userId,
            'publications' => $publications,
            'statuscodes' => $this->statusCodes,
            'appNavigation' => $this->navigation->getTemplate($filter),
            'filter' => $filter,
        ];

        return new TemplateResponse(
            $this->appName,
            'body',
            $params
        );
    }

    /**
     * XHR request endpoint for token setter
     *
     * @return          JSONResponse
     * @NoAdminRequired
     */
    public function setToken()
    {
        $param = $this->request->getParams();
        $error = false;
        if (!is_array($param)
            || !array_key_exists('token', $param)
        ) {
            $error = 'Parameters gotten from UI are no array or they are missing';
        }
        $token = $param['token'];

        if (!is_string($token)) {
            $error = 'Problems while parsing fileid or publishToken';
        }
        $userId = \OC::$server->getUserSession()->getUser()->getUID();
        if (strlen($userId) <= 0) {
            $error = 'No user configured for session';
        }
        if (($error)) {
            Util::writeLog('b2sharebridge', $error, 3);
            return new JSONResponse(
                [
                    'message'=>'Internal server error, contact the EUDAT helpdesk',
                    'status' => 'error'
                ]
            );
        }


        Util::writeLog('b2sharebridge', "saving API token", 3);
        $this->config->setUserValue($userId, $this->appName, "token", $token);
        return new JSONResponse(
            [
                "data" => ["message" => "Saved"],
                "status" => "success"
            ]
        );
    }

    /**
     * XHR request endpoint for token setter
     *
     * @return          JSONResponse
     * @NoAdminRequired
     */
    public function deleteToken()
    {
        Util::writeLog('b2sharebridge', 'Deleting API token', 3);
        $userId = \OC::$server->getUserSession()->getUser()->getUID();
        if (strlen($userId) <= 0) {
            $error = 'No user configured for session';
        }
        if (($error)) {
            Util::writeLog('b2sharebridge', $error, 3);
            return new JSONResponse(
                [
                    'message'=>'Internal server error, contact the EUDAT helpdesk',
                    'status' => 'error'
                ]
            );
        }

        $this->config->setUserValue($userId, $this->appName, 'token', '');
    }

    /**
     * XHR request endpoint for token setter
     *
     * @return          JSONResponse
     * @NoAdminRequired
     */
    public function getTabViewContent()
    {
        Util::writeLog('b2sharebridge', 'serving tab view', 3);
        $url = $this->config->getAppValue(
            'b2sharebridge',
            'publish_baseurl'
        );
        $userId = \OC::$server->getUserSession()->getUser()->getUID();
        //$token = $this->config->getUserValue($userId, $this->appName, "token");
        //TODO serve a warning when token is not set
        $url = $url."/api/communities";
        Util::writeLog('b2sharebridge', "fetching ".$url, 3);
        $json = $this->getSslPage($url);
        //TODO: Unhappy flow
        $data = json_decode($json, true)['hits']['hits'];
        Util::writeLog("b2sharebridge", "JSON: " .$data, 3);
        return $data;
    }

    /**
     * Fetch url for json, currently insecure because ssl validation turned off.
     *
     * @param \string $url URL to fetch
     *
     * @return \string Response
     *
     * @NoAdminRequired
     */
    function getSslPage($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_REFERER, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $check_ssl = $this->config->getAppValue(
            'b2sharebridge',
            'check_ssl',
            '1'
        );
        if (!$check_ssl) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }
}
