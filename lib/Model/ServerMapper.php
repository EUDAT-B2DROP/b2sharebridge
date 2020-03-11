<?php
/**
 * NextCloud - B2sharebridge App
 *
 * PHP Version 5-7
 *
 * @category  Nextcloud
 * @package   B2shareBridge
 * @author    EUDAT <b2drop-devel@postit.csc.fi>
 * @copyright 2020 EUDAT, CSC
 * @license   AGPL3 https://github.com/EUDAT-B2DROP/b2sharebridge/blob/master/LICENSE
 * @link      https://github.com/EUDAT-B2DROP/b2sharebridge.git
 */

namespace OCA\B2shareBridge\Model;

use OCP\AppFramework\Db\Mapper;
use OCP\IDBConnection;
use OCP\Util;

class ServerMapper extends Mapper {
    public function __construct(IDBConnection $db) {
        parent::__construct(
            $db,
            'b2sharebridge_server',
            '\OCA\B2shareBridge\Model\Server'
        );
    }

    public function find($id) {
        $sql = 'SELECT * FROM `' . $this->tableName . '`WHERE `id` = ?';
        return $this->findEntity($sql, [$id]);
    }

    public function findAll() {
        $sql = 'SELECT * FROM ' . $this->tableName;
        return $this->findEntities($sql);
    }
}