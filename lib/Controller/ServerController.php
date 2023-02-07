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


class ServerController extends Controller
{
    private string $userId;
    private ServerMapper $mapper;
    private IJobList $joblist;
    private LoggerInterface $logger;

    public function __construct(
        $appName,
        IRequest $request,
        ServerMapper $mapper,
        IJobList $jobList,
        LoggerInterface $logger,
        $userId
    )
    {
        parent::__construct($appName, $request);
        $this->mapper = $mapper;
        $this->userId = $userId;
        $this->joblist = $jobList;
        $this->logger = $logger;
    }

    /**
     * @NoAdminRequired
     *
     * @throws Exception
     */
    public function listServers(): array
    {
        return $this->mapper->findAll();
    }

    /**
     * @throws MultipleObjectsReturnedException
     * @throws DoesNotExistException
     * @throws Exception
     */
    public function saveServer($server): JSONResponse
    {
        if (!$this->checkServer($server)) {
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
            $server_entity = $this->mapper->find($server['id']);
            $update_communities = $server['publishUrl'] !== $server_entity->getPublishUrl();
        } else {
            $this->logger->info("Creating new b2share server", ["app" => Application::APP_ID]);
            $server_entity = new Server();
            $update_communities = true;
        }

        // update props
        $server_entity->setName($server['name']);
        $server_entity->setPublishUrl($server['publishUrl']);
        if (array_key_exists("maxUploads", $server))
            $server_entity->setMaxUploads($server['maxUploads']);
        if (array_key_exists("maxUploadFilesize", $server))
            $server_entity->setMaxUploadFilesize($server['maxUploadFilesize']);
        if (array_key_exists("checkSsl", $server))
            $server_entity->setCheckSsl($server['checkSsl']);

        // update database
        if ($server_exists) {
            $this->mapper->update($server_entity);
            $this->logger->info("User " . $this->userId . " updated '" . $server['name'] . "'", ["app" => Application::APP_ID]);
        } else {
            $this->mapper->insert($server_entity);
            $this->logger->info("User " . $this->userId . " created '" . $server['name'] . "'", ["app" => Application::APP_ID]);
        }

        // replace job to get communities instantly if required
        if ($update_communities) {
            $this->joblist->remove(B2shareCommunityFetcher::class);
            $this->joblist->add(B2shareCommunityFetcher::class);
        }

        return new JSONResponse(
            [
                "message" => "OK",
            ],
            Http::STATUS_OK
        );
    }

    /**
     * @depreacted use saveServer instead
     * @throws MultipleObjectsReturnedException
     * @throws DoesNotExistException
     * @throws Exception
     */
    public function saveServers($servers): array
    {
        foreach ($servers as $server) {
            if (array_key_exists('id', $server)) {
                $old = $this->mapper->find($server['id']);
                $old->setName($server['name']);
                $old->setPublishUrl($server['publishUrl']);
                $this->mapper->update($old);
            } else {
                $newServer = new Server();
                $newServer->setName($server['name']);
                $newServer->setPublishUrl($server['publishUrl']);
                $this->mapper->insert($newServer);
            }
        }
        // replace job to get communities instantly
        $this->joblist->remove(B2shareCommunityFetcher::class);
        $this->joblist->add(B2shareCommunityFetcher::class);

        return $this->mapper->findAll();
    }

    /**
     * @throws MultipleObjectsReturnedException
     * @throws Exception
     */
    public function deleteServer($id): JSONResponse
    {
        try {
            $this->mapper->delete($this->mapper->find($id));
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

    private function checkServer($server): bool
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