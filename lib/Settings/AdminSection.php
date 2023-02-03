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
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\Settings\IIconSection;

/**
 * Creates the admin settings section
 *
 * @category Owncloud
 * @package  B2shareBridge
 * @author   EUDAT <b2drop-devel@postit.csc.fi>
 * @license  AGPL3 https://github.com/EUDAT-B2DROP/b2sharebridge/blob/master/LICENSE
 * @link     https://github.com/EUDAT-B2DROP/b2sharebridge.git
 */
class AdminSection implements IIconSection
{
    private IL10N $_l;

    private IURLGenerator $_url;

    /**
     * {@inheritdoc}
     *
     * @param IURLGenerator $url URL generator used to link to the image
     * @param IL10N         $l   Language support
     */
    public function __construct(IURLGenerator $url, IL10N $l) 
    {
        $this->_url = $url;
        $this->_l = $l;
    }

    /**
     * {@inheritdoc}
     *
     * @return string the app id, 'b2sharebridge'
     */
    public function getID(): string
    {
        return Application::APP_ID;
    }

    /**
     * {@inheritdoc}
     *
     * @return string the name, 'EUDAT'
     */
    public function getName() 
    {
        return 'EUDAT';
    }

    /**
     * {@inheritdoc}
     *
     * @return int the arrang priority, 75
     */
    public function getPriority(): int
    {
        return 75;
    }

     /**
      * {@inheritdoc}
      *
      * @return string Url of the icon shown in the admin settings
      */
    public function getIcon(): string
    {
        return $this->_url->imagePath(Application::APP_ID, 'eudat_logo.png');
    }
}
