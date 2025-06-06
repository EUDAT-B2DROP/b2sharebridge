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

use OCA\B2shareBridge\Publish\B2ShareAPI;
use OCA\B2shareBridge\Publish\B2ShareV2;
use OCA\B2shareBridge\Publish\B2ShareV3;
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
    protected $version;

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
        $this->addType('version', 'integer');
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
            'checkSsl' => $this->getCheckSsl(),
            'version' => $this->getVersion(),
        ];
    }

    /**
     * To String
     * 
     * @return string
     */
    public function __toString(): string
    {
        return "Server with id " . $this->id . " and name " . $this->getName() . " and publishUrl " . $this->getPublishUrl() . " v" . $this->getVersion();
    }

    /**
     * Returns the B2Share API depending on the server
     * 
     * @throws \BadMethodCallException when the server has an unknown version
     * 
     * @return \OCA\B2shareBridge\Publish\B2ShareAPI B2Share API object
     */
    public function getPublisher(): B2ShareAPI
    {
        if ($this->getVersion() == 2) {
            return \OC::$server->query(B2ShareV2::class);
        } else if ($this->getVersion() == 3) {
            return \OC::$server->query(B2ShareV3::class);
        }
        $version = $this->getVersion();
        throw new \BadMethodCallException("Unknown B2Share version v$version");
    }
}
