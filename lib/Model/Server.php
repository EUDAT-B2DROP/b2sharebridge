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
    protected $name;
    protected $publishUrl;

    public function __construct() {
        $this->addType('id', 'string');
        $this->addType('name', 'string');
        $this->addType('publishUrl', 'string');
    }

    public function jsonSerialize() {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'publishUrl' => $this->publishUrl
        ];
    }

    public function __toString() {
        return "Server with id " . $this->id . " and name " . $this->name . " and publishUrl " . $this->publishUrl;
    }
}
