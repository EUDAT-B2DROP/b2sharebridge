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

namespace OCA\B2shareBridge\Cron;

use OCA\B2shareBridge\AppInfo\Application;
use OCA\B2shareBridge\Model\Community;
use OCA\B2shareBridge\Model\CommunityMapper;
use OCA\B2shareBridge\Model\ServerMapper;
use OCP\DB\Exception;
use OCP\IConfig;
use Psr\Log\LoggerInterface;
use OCP\IDBConnection;
use OCP\BackgroundJob\TimedJob;
use OCP\AppFramework\Utility\ITimeFactory;

/**
 * Register a owncloud Job to regularly fetch b2share api to get communities list
 *
 * @category Owncloud
 * @package  B2shareBridge
 * @author   EUDAT <b2drop-devel@postit.csc.fi>
 * @license  AGPL3 https://github.com/EUDAT-B2DROP/b2sharebridge/blob/master/LICENSE
 * @link     https://github.com/EUDAT-B2DROP/b2sharebridge.git
 */
class B2shareCommunityFetcher extends TimedJob
{

    private IConfig $_config;
    private LoggerInterface $_logger;
    private IDBConnection $_dbconnection;

    /**
     * Create cron that is fetching the b2share communities api
     * with dependency injection
     * 
     * @param \Psr\Log\LoggerInterface               $logger       logger
     * @param \OCP\IConfig                           $config       config
     * @param \OCP\IDBConnection                     $dbconnection connection
     * @param \OCP\AppFramework\Utility\ITimeFactory $time 
     */
    public function __construct(LoggerInterface $logger, IConfig $config, IDBConnection $dbconnection, ITimeFactory $time)
    {
        parent::__construct($time);
        $this->_config = $config;
        $this->_logger = $logger;
        $this->_dbconnection = $dbconnection;
        // Run once an hour
        $this->setInterval(3600);
    }

    /**
     * Cron code to execute
     *
     * @param array $args array of arguments
     * 
     * @throws Exception
     * 
     * @return void
     */
    public function run($args)
    {
        $serverMapper = new ServerMapper($this->_dbconnection);
        $communityMapper = new CommunityMapper($this->_dbconnection);
        $servers = $serverMapper->findAll();
        foreach ($servers as $server) {
            $b2share_communities_url = $server->getPublishUrl() . '/api/communities/';
            $json = $this->_getUrlContent($b2share_communities_url);
            if (!$json) {
                $this->_logger->error(
                    'Fetching the B2SHARE communities API at ' . $b2share_communities_url .
                    ' was not possible.',
                    ['app' => Application::APP_ID]
                );
                continue;
            }
            $communities_fetched = json_decode($json, true)['hits']['hits'];
            if ($communities_fetched === null) {
                $this->_logger->error(
                    'Fetching the B2SHARE communities API at ' . $b2share_communities_url .
                    ' did not return a valid JSON.',
                    ['app' => Application::APP_ID]
                );
                continue;
            }

            $communities_b2share = [];
            foreach ($communities_fetched as $community) {
                $this->_logger->debug(
                    'Fetched community with id: ' . $community['id'] .
                    ' and name: ' . $community['name'] . ' fetched' .
                    ' and restricted_submission: ' . $community['restricted_submission'] . ' from server ' . $server->getName(),
                    ['app' => Application::APP_ID]
                );
                if ($community['restricted_submission'] !== true) {
                    $communities_b2share[$community['id']] = $community['name'];
                } else {
                    $communities_b2share[$community['id']] = $community['name'] . ' ' .
                        "\u{0001F512}";
                }
            }

            $communities_b2drop = [];
            foreach ($communityMapper->findForServer($server->getId()) as $community) {
                $communities_b2drop[$community->getId()] = $community->getName();
            }

            // do we need to remove a community?
            $remove_communities = array_diff_key(
                $communities_b2drop,
                $communities_b2share
            );
            foreach ($remove_communities as $id => $name) {
                $this->_logger->info(
                    'Removing community with id: ' . $id .
                    ' and name: ' . $name . ' after synchronization with b2share',
                    ['app' => Application::APP_ID]
                );
                $communityMapper->deleteCommunity($id);
            }

            // do we need to add a community?
            $add_communities = array_diff_key($communities_b2share, $communities_b2drop);
            foreach ($add_communities as $id => $name) {
                $this->_logger->info(
                    'Adding community with id: ' . $id . ' and name: '
                    . $name . ' after synchronization with B2SHARE server ' . $server->getName(),
                    ['app' => Application::APP_ID]
                );
                $communityMapper->insert(Community::fromParams(['id' => $id, 'name' => $name, 'serverId' => $server->getId()]));
            }
        }

        // Remove orphans

        $communities = $communityMapper->findAll();
        foreach ($communities as $community) {
            $found = false;
            foreach ($servers as $server) {
                if ($server->getId() == $community->getServerId()) {
                    $found = true;
                }
            }
            if (!$found) {
                $this->_logger->info(
                    'Removing orphan community with id: ' . $community->getId()
                );
                $communityMapper->delete($community);
            }
        }
    }

    /**
     * Fetch url for json, opt-in to disable ssl
     *
     * @param string $url URL to fetch
     *
     * @return bool|string Response
     *
     * @NoAdminRequired
     */
    private function _getUrlContent(string $url): bool|string
    {
        $ch = curl_init();
        $config = array(
            CURLOPT_HEADER => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_URL => $url,
            CURLOPT_REFERER => $url,
            CURLOPT_RETURNTRANSFER => true
        );
        $check_ssl = $this->_config->getAppValue(
            Application::APP_ID,
            'check_ssl',
            '1'
        );
        if (!$check_ssl) {
            $config[CURLOPT_SSL_VERIFYHOST] = false;
            $config[CURLOPT_SSL_VERIFYPEER] = false;
        }

        curl_setopt_array($ch, $config);

        $result = curl_exec($ch);
        curl_close($ch);
        if (!$result) {
            return false;
        } else {
            return $result;
        }
    }
}

