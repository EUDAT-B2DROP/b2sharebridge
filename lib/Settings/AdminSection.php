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

use OCP\Settings\ISection;

/**
 * Creates the admin settings section
 *
 * @category Owncloud
 * @package  B2shareBridge
 * @author   EUDAT <b2drop-devel@postit.csc.fi>
 * @license  AGPL3 https://github.com/EUDAT-B2DROP/b2sharebridge/blob/master/LICENSE
 * @link     https://github.com/EUDAT-B2DROP/b2sharebridge.git
 */
class AdminSection implements ISection
{
    /**
     * {@inheritdoc}
     *
     * @return string the app id, 'b2sharebridge'
     */
    public function getID() 
    {
        return 'b2sharebridge';
    }

    /**
     * {@inheritdoc}
     *
     * @return string the section, 'b2sharebridge'
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
    public function getPriority() 
    {
        return 75;
    }
}