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

use OCA\B2shareBridge\AppInfo\Application;
use OCP\AppFramework\Controller;
use OCA\B2shareBridge\Model\Server;
use OCA\B2shareBridge\Model\ServerMapper;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\BackgroundJob\IJobList;
use OCP\DB\Exception;
use OCP\IRequest;
use OCA\B2shareBridge\Cron\B2shareCommunityFetcher;
use Psr\Log\LoggerInterface;

/**
 * Implement a nextcloud Controller for B2SHARE Servers
 *
 * @category Nextcloud
 * @package  B2shareBridge
 * @author   EUDAT <b2drop-devel@postit.csc.fi>
 * @license  AGPL3 https://github.com/EUDAT-B2DROP/b2sharebridge/blob/master/LICENSE
 * @link     https://github.com/EUDAT-B2DROP/b2sharebridge.git
 */
class ServerController extends Controller
{
    private string $_userId;
    private ServerMapper $_mapper;
    private IJobList $_joblist;
    private LoggerInterface $_logger;

    /**
     * Summary of __construct
     *
     * @param string          $appName App Name
     * @param IRequest        $request Request
     * @param ServerMapper    $mapper  Server Mapper
     * @param IJobList        $jobList Nextcloud Server Job List interface
     * @param LoggerInterface $logger  Logger
     * @param string          $userId  User ID
     */
    public function __construct(
        $appName,
        IRequest $request,
        ServerMapper $mapper,
        IJobList $jobList,
        LoggerInterface $logger,
        $userId
    ) {
        parent::__construct($appName, $request);
        $this->_mapper = $mapper;
        $this->_userId = $userId;
        $this->_joblist = $jobList;
        $this->_logger = $logger;
    }

    /**
     * List all B2SHARE Servers
     * 
     * @NoAdminRequired
     *
     * @throws Exception
     * 
     * @return array List of Servers
     */
    public function listServers(): array
    {
        return $this->_mapper->findAll();
    }

    /**
     * Save B2SHARE Server
     * 
     * @param $server B2SHARE Server, see ServerMapper
     * 
     * @throws MultipleObjectsReturnedException
     * @throws DoesNotExistException
     * @throws Exception
     * 
     * @return JSONResponse
     */
    public function saveServer($server): JSONResponse
    {
        if (!$this->_checkServer($server)) {
            return new JSONResponse(
                [
                    "message" => "Validation failed",
                ],
                Http::STATUS_BAD_REQUEST
            );
        }

        // get server entity
        $server_exists = array_key_exists("id", $server);
        if ($server_exists) {
            $server_entity = $this->_mapper->find($server['id']);
            $update_communities = $server['publishUrl'] !== $server_entity->getPublishUrl();
        } else {
            $this->_logger->info("Creating new b2share server", ["app" => Application::APP_ID]);
            $server_entity = new Server();
            $update_communities = true;
        }

        // update props
        $server_entity->setName($server['name']);
        $server_entity->setPublishUrl($server['publishUrl']);
        if (array_key_exists("maxUploads", $server)) {
            $server_entity->setMaxUploads($server['maxUploads']);
        }
        if (array_key_exists("maxUploadFilesize", $server)) {
            $server_entity->setMaxUploadFilesize($server['maxUploadFilesize']);
        }
        if (array_key_exists("checkSsl", $server)) {
            $server_entity->setCheckSsl($server['checkSsl']);
        }

        // update database
        if ($server_exists) {
            $this->_mapper->update($server_entity);
            $this->_logger->info("User " . $this->_userId . " updated '" . $server['name'] . "'", ["app" => Application::APP_ID]);
        } else {
            $this->_mapper->insert($server_entity);
            $this->_logger->info("User " . $this->_userId . " created '" . $server['name'] . "'", ["app" => Application::APP_ID]);
        }

        // replace job to get communities instantly if required
        if ($update_communities) {
            $this->_joblist->remove(B2shareCommunityFetcher::class);
            $this->_joblist->add(B2shareCommunityFetcher::class);
        }

        return new JSONResponse(
            [
                "message" => "OK",
            ],
            Http::STATUS_OK
        );
    }

    /**
     * Save multiple servers at once
     * 
     * @param array $servers List of Servers
     * 
     * @deprecated use saveServer instead
     * 
     * @throws MultipleObjectsReturnedException
     * @throws DoesNotExistException
     * @throws Exception
     * 
     * @return array List of All Servers
     */
    public function saveServers($servers): array
    {
        foreach ($servers as $server) {
            if (array_key_exists('id', $server)) {
                $old = $this->_mapper->find($server['id']);
                $old->setName($server['name']);
                $old->setPublishUrl($server['publishUrl']);
                $this->_mapper->update($old);
            } else {
                $newServer = new Server();
                $newServer->setName($server['name']);
                $newServer->setPublishUrl($server['publishUrl']);
                $this->_mapper->insert($newServer);
            }
        }
        // replace job to get communities instantly
        $this->_joblist->remove(B2shareCommunityFetcher::class);
        $this->_joblist->add(B2shareCommunityFetcher::class);

        return $this->_mapper->findAll();
    }

    /**
     * Delete Server by ID
     * 
     * @param $id Server ID
     * 
     * @throws MultipleObjectsReturnedException
     * @throws Exception
     * 
     * @return JSONResponse
     */
    public function deleteServer($id): JSONResponse
    {
        try {
            $this->_mapper->delete($this->_mapper->find($id));
        } catch (DoesNotExistException) {
            return new JSONResponse(
                [
                    'message' => 'Server does not exist',
                ],
                Http::STATUS_NOT_FOUND
            );
        }
        return new JSONResponse(
            [
                'message' => 'OK',
            ],
            Http::STATUS_OK
        );

    }

    /**
     * Check Server attributes
     * 
     * @param mixed $server Dict for example
     * 
     * @return bool
     */
    private function _checkServer($server): bool
    {
        $required_keys = array("name", "publishUrl");
        foreach ($required_keys as $key) {
            if (!array_key_exists($key, $server)) {
                return false;
            }
        }
        if (!strlen($server["name"]) or !strlen($server["publishUrl"])) {
            return false;
        }
        return true;
    }
}