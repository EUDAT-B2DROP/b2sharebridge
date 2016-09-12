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

namespace OCA\B2shareBridge\Db;

use OCP\AppFramework\Db\Entity;
use OCP\IDBConnection;
use OCP\AppFramework\Db\Mapper;

/**
 * Work on a database table
 *
 * @category Owncloud
 * @package  B2shareBridge
 * @author   EUDAT <b2drop-devel@postit.csc.fi>
 * @license  AGPL3 https://github.com/EUDAT-B2DROP/b2sharebridge/blob/master/LICENSE
 * @link     https://github.com/EUDAT-B2DROP/b2sharebridge.git
 */
class StatusCodeMapper extends Mapper
{

    /**
     * Create the database mapper
     *
     * @param IDBConnection $db the database connection to use
     */
    public function __construct(IDBConnection $db)
    {
        parent::__construct(
            $db,
            'b2sharebridge_status_code',
            '\OCA\B2shareBridge\Db\StatusCode'
        );
    }

    /**
     * Find a database entry for a status code
     *
     * @param string $status_code statusCode to find a transfer entry for
     *
     * @throws \OCP\AppFramework\Db\DoesNotExistException if not found
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException if more th one
     *
     * @return Entity
     */
    public function find($status_code)
    {
        $sql = 'SELECT * FROM `*PREFIX*b2sharebridge_status_code` '
            . 'WHERE `status_code` = ?';
        return $this->findEntity($sql, [$status_code]);
    }
    
     /**
     * Return the number of currently lsited status codes
     *
     * @throws \OCP\AppFramework\Db\DoesNotExistException if not found
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException if more th one
     *
     * @return int number of status codes
     */
    public function findCountForStatusCodes()
    {
        $sql = 'SELECT COUNT(*) FROM `*PREFIX*b2sharebridge_status_codes`'
        return $this->execute($sql)->fetchColumn();
    }
    
     /**
     * Insert a new status code with message
     *
     * @param entity $code contains statusCode and message
     *
     * @throws \OCP\AppFramework\Db\DoesNotExistException if not found
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException if more th one
     *
     * @return int number of active publishs per user
     */
    public function insertStatusCode($code)
    {
        $this->insert($code);
    }

}
