<?php
/**
 * ownCloud - eudat
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the LICENSE file.
 *
 * @author EUDAT <b2drop-devel@postit.csc.fi>
 * @copyright EUDAT 2015
 */

/**
 * Interface that needs to be implemented for every backend that could get files from b2drop
 *
 */

namespace OCA\Eudat\Publish;

interface IPublish {

    public function create($token, $filename);

    public function upload($filehandle, $filesize);

    public function finalize();

}
