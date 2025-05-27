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
class B2share implements IPublish
{
    protected LoggerInterface $logger;
    protected string $file_upload_url;
    protected string $error_message;

    private Curl $_curl;

    /**
     * Create object for actual upload
     *
     * @param IConfig         $_config ignored
     * @param LoggerInterface $logger  logger
     * @param Curl            $curl    curl
     */
    public function __construct(IConfig $_config, LoggerInterface $logger, Curl $curl)
    {
        $this->logger = $logger;
        $this->_curl = $curl;
    }

    /**
     * Activate or deactive SSL check
     * 
     * @param bool $checkSsl check SSL
     * 
     * @return void
     */
    public function setCheckSSL(bool $checkSsl)
    {

    }

    /**
     * Get the portion of the file upload URL
     * filename + access_token still need to be pasted
     *
     * @return string the file_upload_url for the files bucket
     */
    public function getFileUploadUrlPart(): string
    {
        return $this->file_upload_url;
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
     * @return string             draftId
     */
    public function create(
        string $token,
        string $community,
        string $open_access,
        string $title,
        Server $server
    ): string {
        //now settype("false","boolean") evaluates to true, so:
        $b_open_access = false;
        if ($open_access === "true") {
            $b_open_access = true;
        }
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

        $version_slash = $server->getVersion() == 2 ? '/' : '';
        $post_url = "{$server->getPublishUrl()}/api/records{$version_slash}?access_token={$token}";

        $response = $this->_curl->post($post_url, $data);
        if (!$response) {
            return "";
        } else {
            $body_encoded = mb_convert_encoding($response, 'UTF-8', mb_list_encodings());
            $results = json_decode($body_encoded, false);

            if (property_exists($results, 'links')
                && property_exists($results->links, 'self')
                && property_exists($results->links, 'files')
            ) {
                $this->file_upload_url
                    = $results->links->files;
                return "{$results->id}";
            } else {
                $this->error_message = "Something went wrong in uploading.";
                if (property_exists($results, 'status')) {
                    if ($results->status === 403) {
                        $this->error_message = "403 - Authorization Required";
                    }
                }
                return "";
            }
        }
    }

    public function getDraft(Server $server, string $draftId, string $token)
    {
        $url = "{$server->getPublishUrl()}/api/records/{$draftId}/draft?access_token={$token}";
        $res = $this->_curl->request($url);
        $this->logger->debug($url);
        $this->logger->debug($res);
        return json_decode($res, true);
    }

    public function getDraftUrl(Server $server, string $draftId)
    {
        if ($server->getVersion() == 2) {
            return "{$server->getPublishUrl()}/records/{$draftId}/edit";
        } else {
            return "{$server->getPublishUrl()}/uploads/{$draftId}";
        }
    }

    /**
     * Create upload object but do not the upload here
     *
     * @param string $file_upload_url the upload_url for the files bucket
     * @param mixed  $filehandle      file handle
     * @param string $filesize        local filename of file that should be submitted
     *
     * @return bool
     */
    public function upload(string $file_upload_url, mixed $filehandle, string $filesize): bool
    {
        return $this->_curl->upload($file_upload_url, $filehandle, $filesize);
    }
}
