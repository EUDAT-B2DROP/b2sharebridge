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

use OCA\B2shareBridge\AppInfo\Application;
use OCA\B2shareBridge\Model\ServerMapper;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\DB\Exception;
use OCP\IConfig;
use OCP\Settings\ISettings;
use OCP\Util;

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
    private IConfig $_config;
    private ServerMapper $_mapper;
    private string $_userId;

    /**
     * Constructors construct.
     *
     * @param IConfig      $config Nextcloud config container
     * @param ServerMapper $mapper Server Mapper
     * @param string       $userId User ID
     */
    public function __construct(IConfig $config, ServerMapper $mapper, string $userId)
    {
        $this->_config = $config;
        $this->_mapper = $mapper;
        $this->_userId = $userId;
    }

    /**
     * Create Admin menu content
     *
     * @return TemplateResponse
     * @throws Exception
     */
    public function getForm(): TemplateResponse
    {
        Util::addScript(Application::APP_ID, 'b2sharebridge-vendors');
        Util::addScript(Application::APP_ID, 'b2sharebridge-settingspersonal');
        return new TemplateResponse(Application::APP_ID, 'settings-personal');
    }

    /**
     * Actual section name to use
     *
     * @return string the section, 'b2sharebridge'
     */
    public function getSection(): string
    {
        return 'b2sharebridge';
    }

    /**
     * Where to show the section
     *
     * @return int 0
     */
    public function getPriority(): int
    {
        return 0;
    }
}
