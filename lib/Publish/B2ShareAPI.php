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

namespace OCA\B2shareBridge\Publish;

use OCP\IConfig;
use Psr\Log\LoggerInterface;
use OCA\B2shareBridge\Model\Server;
use OCA\B2shareBridge\Util\Curl;

/**
 * Create a interface that must be implemented by publishing backends
 *
 * @category Owncloud
 * @package  B2shareBridge
 * @author   EUDAT <b2drop-devel@postit.csc.fi>
 * @license  AGPL3 https://github.com/EUDAT-B2DROP/b2sharebridge/blob/master/LICENSE
 * @link     https://github.com/EUDAT-B2DROP/b2sharebridge.git
 */
abstract class B2ShareAPI
{
    protected LoggerInterface $logger;

    protected Curl $curl;
    protected IConfig $config;
    protected string $appName;

    /**
     * Placeholder for actually creating a deposit
     *
     * @param string          $appName AppName
     * @param IConfig         $config  access to nextcloud configuration
     * @param LoggerInterface $logger  a logger
     * @param Curl            $curl    curl
     *
     * @return null
     */
    public function __construct(string $appName, IConfig $config, LoggerInterface $logger, Curl $curl)
    {
        $this->curl = $curl;
        $this->logger = $logger;
        $this->config = $config;
        $this->appName = $appName;
    }

    /**
     * Set SSL parameters
     *
     * @param bool $checkSsl check SSL
     * 
     * @return void
     */
    public function setCheckSSL(bool $checkSsl)
    {
        $this->curl->setSSL($checkSsl);
    }

    /**
     * Placeholder for actually creating a deposit
     *
     * @param string $token       users access token
     * @param string $community   Community
     * @param string $open_access Open Access
     * @param string $title       Title
     * @param Server $server      b2share server
     * 
     * @return array[string, string]|bool draftId, file upload url
     */
    abstract public function create(string $token, string $community, string $open_access, string $title, Server $server): array|bool;

    /**
     * Placeholder for upload
     *
     * @param string $file_upload_url Url invenio files bucket URL
     * @param string $filename        Filename
     * @param mixed  $filehandle      Filehandle for upload
     * @param string $filesize        Local filename of file that should be submitted
     * @param string $token           Users access token
     *
     * @return bool success of the upload
     */
    abstract public function upload(string $file_upload_url, string $filename, mixed $filehandle, string $filesize, string $token): bool;

    /**
     * Fetch a draft fully
     * 
     * @param Server $server  Server to get a draft from
     * @param string $draftId Id of the draft
     * @param string $token   B2share token
     * 
     * @return mixed JSON of the draft
     */
    abstract public function getDraft(Server $server, string $draftId, string $token): mixed;

    /**
     * Returns the EDIT url of a draft
     * 
     * @param Server $server  Server
     * @param string $draftId Id of the draft
     * 
     * @return string Edit url
     */
    abstract public function getDraftUrl(Server $server, string $draftId): string;

    /**
     * Delete a draft by ID
     * 
     * @param \OCA\B2shareBridge\Model\Server $server  Server to delete draft from
     * @param string                          $draftId Draft ID
     * @param string                          $token   B2share token
     * 
     * @return bool|string Server answer
     */
    abstract public function deleteDraft(Server $server, string $draftId, string $token);

    /**
     * General request with a validation check
     * 
     * @param \OCA\B2shareBridge\Model\Server $server   Server to check and request from
     * @param string                          $filesUrl url to request
     * @param array                           $header   Optional additional headers for the request
     * @param string                          $type     Optional rest type, e.g. 'GET', 'PUT', ...
     * 
     * @return bool|string False or the result of the request
     */
    protected function requestInternal(Server $server, string $filesUrl, array $header = [], string $type = 'GET'): bool|string
    {
        $serverUrl = $server->getPublishUrl();

        if (str_starts_with($filesUrl, $serverUrl)) {
            return $this->curl->request($filesUrl, $type, $header);
        }

        if (!str_starts_with($filesUrl, "/")) {
            $filesUrl = "/$filesUrl";
        }
        return $this->curl->request("$serverUrl$filesUrl", $type, $header);
    }

    /**
     * Download a file from b2share and return it's content
     * 
     * @param \OCA\B2shareBridge\Model\Server $server      Server
     * @param string                          $filesUrl    Relative URL of the file
     * @param string                          $accessToken AccessToken
     * 
     * @return string
     */
    abstract public function request(Server $server, string $filesUrl, string $accessToken): string;

    /**
     * Get the B2Share API token
     * 
     * @param Server $server Server obj
     * @param string $userId User id
     * 
     * @return string|null B2Share API token
     */
    abstract public function getAccessToken(Server $server, string $userId): string|null;

    /**
     * Gets the B2Share user id
     * 
     * @param \OCA\B2shareBridge\Model\Server $server Server obj
     * @param string                          $token  B2Share API token
     * 
     * @return string|null
     */
    abstract public function getB2ShareUserId(Server $server, string $token): string|null;


    /**
     * Fetch communities from B2Share
     * 
     * @param \OCA\B2shareBridge\Model\Server $server Server obj
     * 
     * @return bool|string False or request answer
     */
    abstract public function fetchCommunities(Server $server): string|bool;

    /**
     * Create a new version (draft) out of a publication
     * 
     * @param \OCA\B2shareBridge\Model\Server $server   Server obj
     * @param string                          $recordId Record ID
     * @param string                          $token    B2Share API token
     * 
     * @return void
     */
    abstract public function nextVersion(Server $server, string $recordId, string $token): string|bool;

    /**
     * Gets user records for a single server
     * 
     * @param Server $server Server object
     * @param string $userId User id
     * @param bool   $draft  True for (only) draft records, else false
     * @param int    $page   Page number, you are limited to 50 records by B2SHARE Api
     * @param int    $size   Page size, number of records per page
     * 
     * @return array|null Returns null, if no token is set
     */
    abstract public function getUserRecords(Server $server, string $userId, bool $draft, int $page, int $size): array|null;

    /**
     * Check, if a token is valid
     * 
     * @param Server      $server Server object
     * @param string|null $token  Token to check
     * 
     * @return bool
     */
    abstract public function checkTokenIsValid(Server $server, string|null $token): bool;
}
