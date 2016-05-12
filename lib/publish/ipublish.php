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

/**
 * Interface that needs to be implemented for every backend that could get files from b2drop
 */

namespace OCA\B2shareBridge\Publish;

interface IPublish
{

    public function create($token, $filename);

    public function upload($filehandle, $filesize);

    public function finalize();

}
