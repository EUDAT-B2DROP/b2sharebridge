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
     * @param string $file_upload_url url invenio files bucket URL
     * @param mixed  $filehandle      users access token
     * @param string $filesize        local filename of file that should be submitted
     *
     * @return bool success of the upload
     */
    public function upload(string $file_upload_url, mixed $filehandle, string $filesize): bool
    {
        return $this->curl->upload($file_upload_url, $filehandle, $filesize);
    }

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
     * 
     * @return bool|string False or the result of the request
     */
    public function request(Server $server, string $filesUrl): bool|string
    {
        if (!str_starts_with($filesUrl, $server->getPublishUrl())) {
            $this->logger->error("Invalid sites call detected. $filesUrl");
            return false;
        }
        return $this->curl->request($filesUrl);
    }

    /**
     * Get the B2Share API token
     * 
     * @param Server $server Server obj
     * @param string $userId User id
     * 
     * @return string B2Share API token
     */
    abstract public function getAccessToken(Server $server, string $userId): string;

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
         * @return array
         */
    abstract public function getUserRecords(Server $server, string $userId, bool $draft, int $page, int $size): array;
}
