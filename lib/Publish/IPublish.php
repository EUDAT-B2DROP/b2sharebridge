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

/**
 * Create a interface that must be implemented by publishing backends
 *
 * @category Owncloud
 * @package  B2shareBridge
 * @author   EUDAT <b2drop-devel@postit.csc.fi>
 * @license  AGPL3 https://github.com/EUDAT-B2DROP/b2sharebridge/blob/master/LICENSE
 * @link     https://github.com/EUDAT-B2DROP/b2sharebridge.git
 */
interface IPublish
{
    /**
     * Placeholder for actually creating a deposit
     *
     * @param IConfig         $config access to nextcloud configuration
     * @param LoggerInterface $logger a logger
     *
     * @return null
     */
    public function __construct(IConfig $config, LoggerInterface $logger);

    /**
     * Placeholder for actually creating a deposit
     *
     * @param  string $token        users access token
     * @param  string $community
     * @param  string $open_access
     * @param  string $title
     * @param  string $api_endpoint
     * @return string
     */
    public function create(string $token, string $community, string $open_access, string $title, string $api_endpoint): string;

    /**
     * Placeholder for upload
     *
     * @param string $file_upload_url url invenio files bucket URL
     * @param mixed  $filehandle      users access token
     * @param string $filesize        local filename of file that should be submitted
     *
     * @return bool success of the upload
     */
    public function upload(string $file_upload_url, mixed $filehandle, string $filesize): bool;
}
