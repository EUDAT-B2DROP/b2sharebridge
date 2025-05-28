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

use OCA\B2shareBridge\Exceptions\UploadNotificationException;
use OCA\B2shareBridge\Model\Server;
use OCA\B2shareBridge\Util\Curl;
use OCP\IConfig;
use Psr\Log\LoggerInterface;

/**
 * Implement a backend that is able to move data from owncloud to B2SHARE
 *
 * @category Owncloud
 * @package  B2shareBridge
 * @author   EUDAT <b2drop-devel@postit.csc.fi>
 * @license  AGPL3 https://github.com/EUDAT-B2DROP/b2sharebridge/blob/master/LICENSE
 * @link     https://github.com/EUDAT-B2DROP/b2sharebridge.git
 */
class B2ShareV2 extends B2ShareAPI
{
    protected string $error_message;


    /**
     * Create object for actual upload
     *
     * @param string          $appName AppName
     * @param IConfig         $_config ignored
     * @param LoggerInterface $logger  logger
     * @param Curl            $curl    curl
     */
    public function __construct(string $appName, IConfig $_config, LoggerInterface $logger, Curl $curl)
    {
        parent::__construct($appName, $_config, $logger, $curl);
    }

    /**
     * Get the error message from HTTP service
     *
     * @return string error message from the http interaction
     */
    public function getErrorMessage(): string
    {
        return $this->error_message;
    }


    /**
     * Publish to url via post, use uuid for filename. Use a token and set expect
     * to empty just as a workaround for local issues
     *
     * @param string $token       users access token
     * @param string $community   id of community metadata schema, defaults to EUDAT
     * @param string $open_access publish as open access, defaults to false
     * @param string $title       actual title of the deposit
     * @param Server $server      b2share server
     *
     * @return array[string, string]|bool draftId, file upload url
     */
    public function create(
        string $token,
        string $community,
        string $open_access,
        string $title,
        Server $server
    ): array|bool {

        // prepare post request
        $b_open_access = $open_access === "true";
        $data = json_encode(
            [
                'community' => $community,
                'titles' => [
                    [
                        'title' => $title
                    ]
                ],
                'open_access' => $b_open_access
            ]
        );

        $post_url = "{$server->getPublishUrl()}/api/records/?access_token={$token}";

        $response = $this->curl->post($post_url, $data);

        // check response
        if (!$response) {
            return false;
        }

        $body_encoded = mb_convert_encoding($response, 'UTF-8', mb_list_encodings());
        $results = json_decode($body_encoded, false);

        if (!property_exists($results, 'links')
            || !property_exists($results->links, 'self')
            || !property_exists($results->links, 'files')
        ) {
            $this->error_message = "Something went wrong in uploading.";
            if (property_exists($results, 'status')) {
                if ($results->status === 403) {
                    $this->error_message = "403 - Authorization Required";
                }
            }
            return false;
        }
        // success
        $fileUploadUrl = $results->links->files;
        return ["{$results->id}", $fileUploadUrl];
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
    public function getDraft(Server $server, string $draftId, string $token): mixed
    {
        $url = "{$server->getPublishUrl()}/api/records/{$draftId}/draft?access_token={$token}";
        $res = $this->curl->request($url);
        $this->logger->debug($url);
        $this->logger->debug($res);
        return json_decode($res, true);
    }

    /**
     * Returns the EDIT url of a draft
     * 
     * @param Server $server  Server
     * @param string $draftId Id of the draft
     * 
     * @return string Edit url
     */
    public function getDraftUrl(Server $server, string $draftId): string
    {
        return "{$server->getPublishUrl()}/records/{$draftId}/edit";
    }

    /**
     * Delete a draft by ID
     * 
     * @param \OCA\B2shareBridge\Model\Server $server Server to delete draft from
     * @param string $draftId Draft ID
     * @param string $token B2share token
     * 
     * @return bool|string Server answer
     */
    public function deleteDraft(Server $server, string $draftId, $token)
    {
        $serverUrl = $server->getPublishUrl();
        $urlPath = "$serverUrl/api/records/$draftId/draft?access_token=$token";
        $output = $this->curl->request($urlPath, "DELETE");
        return $output;
    }

    /**
     * Get the B2Share API token
     * 
     * @param Server $server 
     * @param string $userId
     * 
     * @return string B2Share API token
     */
    public function getAccessToken(Server $server, string$userId): string
    {
        return $this->config->getUserValue($userId, $this->appName, 'token_' . $server->getId(), null);
    }

    /**
     * Gets the B2Share user id
     * 
     * @param \OCA\B2shareBridge\Model\Server $server Server obj
     * @param string $token B2Share API token
     * 
     * @return string|null
     */
    public function getB2ShareUserId(Server $server, string $token): string|null
    {
        $serverUrl = $server->getPublishUrl();
        $response = $this->curl->request("$serverUrl/api/user/?access_token=$token");
        if (!$response) {
            return null;
        }
        $b2accessIdResponse = json_decode($response, true);
        if (array_key_exists("id", $b2accessIdResponse)) {
            return $b2accessIdResponse["id"];
        }
        return null;
    }
}
