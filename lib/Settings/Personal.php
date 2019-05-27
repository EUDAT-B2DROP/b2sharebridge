<?php
/**
 * OwnCloud - B2sharebridge App
 *
 * PHP Version 7
 *
 * @category  Nextcloud
 * @package   B2shareBridge
 * @author    EUDAT <b2drop-devel@postit.csc.fi>
 * @copyright 2015 EUDAT
 * @license   AGPL3 https://github.com/EUDAT-B2DROP/b2sharebridge/blob/master/LICENSE
 * @link      https://github.com/EUDAT-B2DROP/b2sharebridge.git
 */


namespace OCA\B2shareBridge\Settings;

use OCP\AppFramework\Http\TemplateResponse;
use OCP\IConfig;
use OCP\Settings\ISettings;

/**
 * Creates the personal settings for b2sharebirdge.
 *
 * @category Nextcloud
 * @package  B2shareBridge
 * @author   EUDAT <b2drop-devel@postit.csc.fi>
 * @license  AGPL3 https://github.com/EUDAT-B2DROP/b2sharebridge/blob/master/LICENSE
 * @link     https://github.com/EUDAT-B2DROP/b2sharebridge.git
 */
class Personal implements ISettings
{
    /**
     * Nextcloud config container
     *
     * @var IConfig
     */
    private $_config;

    /**
     * Constructors construct.
     *
     * @param IConfig $config Nextcloud config container
     */
    public function __construct(IConfig $config) 
    {
        $this->_config = $config;
    }

    /**
     * Create Admin menue content
     *
     * @return TemplateResponse
     */
    public function getForm() 
    {
	$userId = \OC::$server->getUserSession()->getUser()->getUID();
        $params = [
            'publish_baseurl' => $this->_config->getAppValue(
                'b2sharebridge', 'publish_baseurl'
            ),
            'b2share_apitoken' => $this->_config->getUserValue(
                $userId, 'b2sharebridge', 'token'
            ),
        ];

        return new TemplateResponse('b2sharebridge', 'settings-personal', $params);
    }

    /**
     * Actual section name to use
     *
     * @return string the section, 'b2sharebridge'
     */
    public function getSection() 
    {
        return 'b2sharebridge';
    }

    /**
     * Where to show the section
     *
     * @return int 0
     */
    public function getPriority() 
    {
        return 0;
    }
}
