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
namespace OCA\B2shareBridge\Exception;
use Exception;
/**
 * General Controller exception that contains a Http status code
 */
class ControllerValidationException extends Exception
{
    private int $statusCodeHttp;
    public function __construct(string $message, int $statusCode, ?\Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
        $this->statusCodeHttp = $statusCode;
    }

    /**
     * Returns the Http status code of the exception
     *
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->statusCodeHttp;
    }
}

class UploadNotificationException extends Exception
{
    private array $parameters;
    public function __construct(string $message, array $parameters, ?\Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
        $this->parameters = $parameters;
    }

    public function getSubjectParameters()
    {
        return $this->parameters;
    }
}