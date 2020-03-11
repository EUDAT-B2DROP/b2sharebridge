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

use OC\BackgroundJob\TimedJob;
use OCA\B2shareBridge\Model\Community;
use OCA\B2shareBridge\Model\CommunityMapper;
use OCA\B2shareBridge\Model\ServerMapper;


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

    protected $config;
    protected $logger;

    /**
     * Create cron that is fetching the b2share communities api
     *
     * @param IConfig $config we need a config
     * @param ILogger $logger having a logger is always good
     */
    public function __construct(
        IConfig $config = null,
        ILogger $logger = null
    ) {
        if ($config === null || $logger === null) {
            $this->fixDIForJobs();
        } else {
            $this->config = $config;
            $this->logger = $logger;
        }
        $this->setInterval(86400); // 24hrs
    }

    /**
     * Fix cron if no constructor parameters fiven
     *
     * @return null
     */
    protected function fixDIForJobs()
    {
        $this->config = \OC::$server->getConfig();
        $this->logger = \OC::$server->getLogger();
    }

    /**
     * Cron code to execute
     *
     * @param array(string) $args array of arguments
     *
     * @return null
     */
    protected function run($args)
    {
        $serverMapper =  new ServerMapper(\OC::$server->getDatabaseConnection());
        $c_mapper = new CommunityMapper(\OC::$server->getDatabaseConnection());
        $servers = $serverMapper->findAll();
        foreach($servers as $server) {
            $b2share_communities_url = $server->getPublishUrl().'/api/communities/';
            $json = $this->_getUrlContent($b2share_communities_url);
            if (!$json) {
                $this->logger->error(
                    'Fetching the B2SHARE communities API was not possible.',
                    ['app' => 'b2sharebridge']
                );
                return;
            }
            $communities_fetched = json_decode($json, true)['hits']['hits'];
            if ($communities_fetched === null) {
                $this->logger->error(
                    'Fetching the B2SHARE communities API did not return a valid JSON.',
                    ['app' => 'b2sharebridge']
                );
                return;
            }

            $communities_b2share = [];
            foreach ($communities_fetched as $community) {
                $this->logger->debug(
                    'Fetched community with id: ' . $community['id'] .
                    ' and name: ' . $community['name'] . ' fetched' .
                    ' and restricted_submission: '. $community['restricted_submission'] . ' from server ' . $server->getName(),
                    ['app' => 'b2sharebridge']
                );
                if ($community['restricted_submission'] !== true) {
                    $communities_b2share[$community['id']] = $community['name'];
                } else {
                    $communities_b2share[$community['id']] = $community['name'] . ' ' .
                    "\u{0001F512}";
                }
            }

            $communities_b2drop = [];
            foreach ($c_mapper->findForServer($server->getId()) as $community) {
                $communities_b2drop[$community->getId()] = $community->getName();
            }

            // do we need to remove a community?
            $remove_communities = array_diff_key(
                $communities_b2drop,
                $communities_b2share
            );
            foreach ($remove_communities as $id => $name) {
                $this->logger->info(
                    'Removing community with id: ' . $id .
                    ' and name: ' . $name . ' after synchronization with b2share',
                    ['app' => 'b2sharebridge']
                );
                $c_mapper->deleteCommunity($id);
            }

            // do we need to add a community?
            $add_communities = array_diff_key($communities_b2share, $communities_b2drop);
            foreach ($add_communities as $id => $name) {
                $this->logger->info(
                    'Adding community with id: ' . $id . ' and name: '
                    . $name . ' after synchronization with B2SHARE server ' . $server->getName(),
                    ['app' => 'b2sharebridge']
                );
                $c_mapper->insert(Community::fromParams(['id' => $id, 'name' => $name, 'serverId' => $server->getId()]));
            }
        }

        // Remove orphans

        $communities = $c_mapper->findAll();
        foreach($communities as $community) {
            $found = false;
            foreach($servers as $server) {
                if ($server->getId() == $community->getServerId()) {
                    $found = true;
                }
            }
            if ($found == false) {
                $this->logger->info(
                    'Removing orphan community with id: ' . $community->getId()
                );
                $c_mapper->delete($community);
            }
        }
    }

    /**
     * Fetch url for json, opt-in to disable ssl
     *
     * @param \string $url URL to fetch
     *
     * @return \string Response
     *
     * @NoAdminRequired
     */
    private function _getUrlContent($url)
    {
        $ch = curl_init();
        $config = array(
            CURLOPT_HEADER => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_URL => $url,
            CURLOPT_REFERER => $url,
            CURLOPT_RETURNTRANSFER => true
        );
        $check_ssl =  $this->config->getAppValue(
            'b2sharebridge',
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

