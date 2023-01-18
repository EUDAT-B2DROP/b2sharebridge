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
     * @param IConfig $config access to nextcloud configuration
     *
     * @return null
     */
    public function __construct(IConfig $config);

    /**
     * Placeholder for actually creating a deposit
     *
     * @param string $token users access token
     * @param string $filename local filename of file that should be submitted
     *
     * @return null
     */
    public function create($token, $filename);

    /**
     * Placeholder for upload
     *
     * @param string $file_upload_url url invenio files bucket URL
     * @param string $filehandle users access token
     * @param string $filesize local filename of file that should be submitted
     *
     * @return null
     */
    public function upload($file_upload_url, $filehandle, $filesize);
}
