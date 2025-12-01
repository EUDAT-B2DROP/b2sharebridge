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

use OCA\B2shareBridge\AppInfo\Application;
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
class B2ShareV3 extends B2ShareAPI
{

    protected string $file_upload_url;
    protected string $error_message;


    /**
     * Create object for actual upload
     *
     * @param string          $appName AppName
     * @param IConfig         $_config Config
     * @param LoggerInterface $logger  Logger
     * @param Curl            $curl    Curl
     */
    public function __construct(string $appName, IConfig $_config, LoggerInterface $logger, Curl $curl)
    {
        parent::__construct($appName, $_config, $logger, $curl);
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
     * @return array[string, string]|bool draftId, fileUploadUrl
     */
    public function create(
        string $token,
        string $community,
        string $open_access,
        string $title,
        Server $server
    ): array|bool {

        $b_open_access = $open_access === "true";

        // TODO data is missing the community / org
        $data = json_encode(
            [
                "access" => [
                    "record" => $b_open_access ? "public" : "closed",
                    "files" => $b_open_access ? "public" : "closed",
                ],
                "files" => [
                    "enabled" => true
                ],
                "metadata" => [
                    "title" => $title,
                    "creators" => [
                        "person_or_org" => [
                            "name" => $community,
                            "type" => "organizational"
                        ],
                        "affiliations" => [
                            "name" => $community
                        ]
                    ],
                ],
                "type" => "community-submission",
                "publication_date" => date("Y-m-d"),
            ]
        );

        $post_url = "{$server->getPublishUrl()}/api/records";
        $header = $this->_getTokenHeader($token);
        $response = $this->curl->post($post_url, $data, $header);

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
        // TODO this URL might be incorrect
        $header = $this->_getTokenHeader($token);
        $res = $this->requestInternal($server, "/api/records/{$draftId}/draft", $header);
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
        return "{$server->getPublishUrl()}/uploads/{$draftId}";
    }

    /**
     * Delete a draft by ID
     * 
     * @param \OCA\B2shareBridge\Model\Server $server  Server to delete draft from
     * @param string                          $draftId Draft ID
     * @param mixed                           $token   B2share token
     * 
     * @return bool|string Server answer
     */
    public function deleteDraft(Server $server, string $draftId, $token)
    {
        $serverUrl = $server->getPublishUrl();
        // TODO this URL might be incorrect
        $urlPath = "$serverUrl/api/records/$draftId/draft";
        $header = $this->_getTokenHeader($token);
        $output = $this->curl->request($urlPath, "DELETE", $header);
        return $output;
    }

    /**
     * Get the B2Share API token
     * 
     * @param Server $server Server obj
     * @param string $userId User id
     * 
     * @return string B2Share API token
     */
    public function getAccessToken($server, $userId): string|null
    {
        $token = $this->config->getUserValue($userId, $this->appName, 'token_' . $server->getId(), null);
        if (!$this->checkTokenIsValid($server, $token)) {
            return null;
        }
        return $token;
    }

    /**
     * Gets the B2Share user id
     * 
     * @param \OCA\B2shareBridge\Model\Server $server Server obj
     * @param string                          $token  B2Share API token
     * 
     * @return string|null
     */
    public function getB2ShareUserId(Server $server, string $token): string|null
    {
        // TODO this URL might be incorrect
        $header = $this->_getTokenHeader($token);
        $response = $this->requestInternal($server, "/api/user", $header);
        if (!$response) {
            return null;
        }
        $b2accessIdResponse = json_decode($response, true);
        if (array_key_exists("id", $b2accessIdResponse)) {
            return $b2accessIdResponse["id"];
        }
        return null;
    }

    /**
     * Fetch communities from B2Share
     * 
     * @param \OCA\B2shareBridge\Model\Server $server Server obj
     * 
     * @return bool|string False or request answer
     */
    public function fetchCommunities(Server $server): string|bool
    {
        return $this->requestInternal($server, "/api/communities");
    }


    /**
     * Create a new version (draft) out of a publication
     * 
     * @param \OCA\B2shareBridge\Model\Server $server   Server obj
     * @param string                          $recordId Record ID
     * @param string                          $token    B2Share API token
     * 
     * @return void
     */
    public function nextVersion(Server $server, string $recordId, string $token): string|bool
    {
        // TODO URL might be wrong
        $createNextVersionUrl = "{$server->getPublishUrl()}/api/records/$recordId/draft?access_token=$token";
        return $this->curl->post($createNextVersionUrl, '');
    }

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
    public function getUserRecords($server, $userId, $draft, $page, $size): array|null
    {
        $token = $this->getAccessToken($server, $userId);
        if (!$token) {
            return null;
        }

        $params = [
            'is_published' => $draft ? "false" : "true",
            'page' => $page,
            'size' => $size,
            'sort' => 'newest'
        ];

        $httpParams = http_build_query($params);
        $serverUrl = $server->getPublishUrl();
        $urlPath = "$serverUrl/api/user/records?$httpParams";

        $output = $this->request($server, $urlPath, $token);

        if (is_bool($output) && $output === false) {
            return [];
        }
        $outputRecords = json_decode($output, true);
        if (array_key_exists("hits", $outputRecords)) {
            $records = $outputRecords["hits"];

            if (array_key_exists("hits", $records)) {
                return $records;
            }
        } else {
            $this->logger->error("Array key hits does not exist", ['app' => Application::APP_ID]);
            $this->logger->error(print_r($outputRecords, true), ['app' => Application::APP_ID]);
            $this->logger->error("Path: $urlPath", ['app' => Application::APP_ID]);
        }
        return [];
    }

    /**
     * Generates the token header for requests
     * 
     * @param mixed $accessToken AccessToken
     * 
     * @return string[]
     */
    private function _getTokenHeader($accessToken): array
    {
        return [
            "Authorization: Bearer $accessToken",
        ];
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
    public function request(Server $server, string $filesUrl, string $accessToken): string
    {
        $header = $this->_getTokenHeader($accessToken);
        return $this->requestInternal($server, $filesUrl, $header);
    }

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
    public function upload(string $file_upload_url, string $filename, mixed $filehandle, string $filesize, string $token): bool
    {
        $header = $this->_getTokenHeader($token);

        // Step 1 start draft file upload(s)
        $data = [
            [
                "key" => $filename
            ]
        ];
        $filenameEncoded = rawurlencode($filename);
        $response = $this->curl->post($file_upload_url, json_encode($data), $header);
        $this->logger->debug("Step1: " . print_r($response, true), ['app' => Application::APP_ID]);
        // $json_response = json_decode($response, true);
        if (!$response) {
            $this->logger->error("Creating resource for file $filename failed", ['app' => Application::APP_ID]);
            return false;
        }

        // Step 2 Upload file content
        $response = $this->curl->upload("$file_upload_url/$filenameEncoded/content", $filehandle, $filesize, $header);
        $this->logger->debug("Step2: " . print_r($response, true), ['app' => Application::APP_ID]);
        // $json_response = json_decode($response, true);
        if (!$response) {
            $this->logger->error("Creating file upload for file $filename failed", ['app' => Application::APP_ID]);
            return false;
        }

        // Step 3 complete a drift file upload
        $response = $this->curl->post("$file_upload_url/$filenameEncoded/commit", '', $header);
        $this->logger->debug("Step3: " . print_r($response, true), ['app' => Application::APP_ID]);
        // $json_response = json_decode($response, true);
        if (!$response) {
            $this->logger->error("Creating commit for file $filename failed", ['app' => Application::APP_ID]);
            return false;
        }
        return true;
    }

    /**
     * Check, if a token is valid
     * 
     * @param Server      $server Server object
     * @param string|null $token  Token to check
     * 
     * @return bool
     */
    public function checkTokenIsValid(Server $server, string|null $token): bool
    {
        if ($token == "" || $token == null) {
            return false;
        }

        $header = $this->_getTokenHeader($token);
        $response = $this->requestInternal($server, '/api/user/communities', $header, 'GET');
        if (!$response) {
            return false;
        }

        $response = json_decode($response, true);

        if (array_key_exists('status', $response)) {
            if ($response['status'] == 200) {
                return true;
            } elseif ($response['status'] == 403) {
                return false;
            }

            $this->logger->error("Invalid response on token request from $server, response $response");
        } elseif (array_key_exists('hits', $response)) { // I don't understand, why this doesn't contain a status
            return true;
        }
        return false;
    }
}
