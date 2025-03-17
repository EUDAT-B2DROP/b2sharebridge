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

namespace OCA\B2shareBridge\Model;

use OCP\AppFramework\Db\Entity;
use JsonSerializable;

/**
 * Creates a database entity for the deposit status
 *
 * @category Nextcloud
 * @package  B2shareBridge
 * @author   EUDAT <b2drop-devel@postit.csc.fi>
 * @license  AGPL3 https://github.com/EUDAT-B2DROP/b2sharebridge/blob/master/LICENSE
 * @link     https://github.com/EUDAT-B2DROP/b2sharebridge.git
 */
class Server extends Entity implements JsonSerializable
{
    /**
     * DO NOT ADD TYPE HINTS TO THIS
     */
    protected $name;
    protected $publishUrl;
    protected $maxUploads;
    protected $maxUploadFilesize;
    protected $checkSsl;

    /**
     * Construct B2SHARE Server
     */
    public function __construct()
    {
        $this->addType('id', 'string');
        $this->addType('name', 'string');
        $this->addType('publishUrl', 'string');
        $this->addType('maxUploads', 'integer');
        $this->addType('maxUploadFilesize', 'integer');
        $this->addType('checkSsl', 'integer');
    }

    /**
     * To JSON
     * 
     * @return mixed
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->getName(),
            'publishUrl' => $this->getPublishUrl(),
            'maxUploads' => $this->getMaxUploads(),
            'maxUploadFilesize' => $this->getMaxUploadFilesize(),
            'checkSsl' => $this->getCheckSsl()
        ];
    }

    /**
     * To String
     * 
     * @return string
     */
    public function __toString(): string
    {
        return "Server with id " . $this->id . " and name " . $this->getName() . " and publishUrl " . $this->getPublishUrl();
    }
}
