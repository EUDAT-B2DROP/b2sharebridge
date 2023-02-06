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


namespace OCA\B2shareBridge\Settings;

use OCA\B2shareBridge\AppInfo\Application;
use OCP\AppFramework\App;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\DB\Exception;
use OCP\IConfig;
use OCP\Settings\ISettings;
use OCA\B2shareBridge\Model\ServerMapper;

/**
 * Creates the admin settings for b2sharebirdge.
 *
 * @category Owncloud
 * @package  B2shareBridge
 * @author   EUDAT <b2drop-devel@postit.csc.fi>
 * @license  AGPL3 https://github.com/EUDAT-B2DROP/b2sharebridge/blob/master/LICENSE
 * @link     https://github.com/EUDAT-B2DROP/b2sharebridge.git
 */
class Admin implements ISettings
{
    private IConfig $_config;
    private ServerMapper $mapper;

    /**
     * Constructors construct.
     *
     * @param IConfig $config Nextcloud config container
     */
    public function __construct(IConfig $config, ServerMapper $mapper)
    {
        $this->_config = $config;
        $this->mapper = $mapper;
    }


    /**
     * Create Admin menu content
     *
     * @return TemplateResponse
     * @throws Exception
     */
    public function getForm(): TemplateResponse
    {
        $this->mapper->findAll();
        /*$params = [
            'max_uploads' => $this->_config->getAppValue(
                Application::APP_ID, 'max_uploads'
            ),
            'max_upload_filesize' => $this->_config->getAppValue(
                Application::APP_ID, 'max_upload_filesize'
            ),
            'check_ssl' => $this->_config->getAppValue(
                Application::APP_ID, 'check_ssl'
            ),
            'servers' => $this->mapper->findAll()
        ];*/

        return new TemplateResponse(Application::APP_ID, 'settings-admin');
    }

    /**
     * Actual section name to use
     *
     * @return string the section, 'b2sharebridge'
     */
    public function getSection(): string
    {
        return Application::APP_ID . "_admin";
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
