<?php
/**
 * Nextcloud - B2sharebridge App
 *
 * PHP Version 8
 *
 * @category  Nextcloud
 * @package   B2shareBridge
 * @author    EUDAT <b2drop-devel@postit.csc.fi>
 * @copyright 2025 EUDAT
 * @license   AGPL3 https://github.com/EUDAT-B2DROP/b2sharebridge/blob/master/LICENSE
 * @link      https://github.com/EUDAT-B2DROP/b2sharebridge.git
 */

namespace OCA\B2shareBridge\Exceptions;

use Exception;

/**
 * General Controller exception that contains a Http status code
 * 
 * @category  Nextcloud
 * @package   B2shareBridge
 * @author    EUDAT <b2drop-devel@postit.csc.fi>
 * @copyright 2025 EUDAT
 * @license   AGPL3 https://github.com/EUDAT-B2DROP/b2sharebridge/blob/master/LICENSE
 * @link      https://github.com/EUDAT-B2DROP/b2sharebridge.git
 */
class ControllerValidationException extends Exception
{
    private int $_statusCodeHttp;

    /**
     * Summary of __construct
     * 
     * @param string $message    Text Message
     * @param int    $statusCode Http Status code
     * @param mixed  $previous   Previous exception
     */
    public function __construct(string $message, int $statusCode, ?\Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
        $this->_statusCodeHttp = $statusCode;
    }

    /**
     * Returns the Http status code of the exception
     *
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->_statusCodeHttp;
    }
}
