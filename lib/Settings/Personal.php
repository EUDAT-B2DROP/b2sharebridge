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
use OCP\AppFramework\Http\TemplateResponse;
use OCP\DB\Exception;
use OCP\IConfig;
use OCP\Settings\ISettings;
use OCA\B2shareBridge\Model\ServerMapper;

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
    private ServerMapper $mapper;
    private string $userId;

    /**
     * Constructors construct.
     *
     * @param IConfig $config Nextcloud config container
     * @param ServerMapper $mapper
     * @param string $userId
     */
    public function __construct(IConfig $config, ServerMapper $mapper, string $userId)
    {
        $this->_config = $config;
        $this->mapper = $mapper;
        $this->userId = $userId;
    }

    /**
     * Create Admin menu content
     *
     * @return TemplateResponse
     * @throws Exception
     */
    public function getForm(): TemplateResponse
    {
        $serverEntities = $this->mapper->findAll();
        $servers = [];
        foreach($serverEntities as $i => $s) {
            $servers[$i] = ['id' => $s->getId(), 'name' => $s->getName(), 'publishUrl' => $s->getPublishUrl(), 'token' => $this->_config->getUserValue(
                $this->userId, Application::APP_ID, 'token_' . $s->getId()
            )];
        }
        $params = [
            'servers' => $servers
        ];

        return new TemplateResponse(Application::APP_ID, 'settings-personal', $params);
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
