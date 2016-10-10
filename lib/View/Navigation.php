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

namespace OCA\B2shareBridge\View;

use OCP\IURLGenerator;
use OCP\Template;

/**
 * Class Navigation
 *
 * @category Owncloud
 * @package  B2shareBridge
 * @author   EUDAT <b2drop-devel@postit.csc.fi>
 * @license  AGPL3 https://github.com/EUDAT-B2DROP/b2sharebridge/blob/master/LICENSE
 * @link     https://github.com/EUDAT-B2DROP/b2sharebridge.git
 */
class Navigation
{
    /**
     * URL Generator for getting deposits
     *
     * @var IURLGenerator
     */
    protected $URLGenerator;

    /**
     * Construct
     *
     * @param IURLGenerator $URLGenerator url generator for getting deposit lists
     */
    public function __construct(IURLGenerator $URLGenerator)
    {
        $this->URLGenerator = $URLGenerator;
    }

    /**
     * Get a filled navigation menu
     *
     * @param null|string $forceActive Navigation entry
     *
     * @return \OCP\Template
     */
    public function getTemplate($forceActive = null)
    {
        $active = $forceActive ?: $this->active;

        $template = new Template('b2sharebridge', 'navigation', '');
        $entries = $this->getLinkList();

        $template->assign('activeNavigation', $active);
        $template->assign('navigations', $entries);

        return $template;
    }

    /**
     * Get all items for the navigation menu
     *
     * @return array navigation bar entries (id, name, url)
     */
    public function getLinkList()
    {
        $entries = [
            [
                'id' => 'all',
                'name' => 'All Deposits',
                'url' => $this->URLGenerator->linkToRoute(
                    'b2sharebridge.View.depositList'
                ),
            ],
        ];
        $entries[] = [
            'id' => 'pending',
            'name' => 'Pending Deposits',
            'url' => $this->URLGenerator->linkToRoute(
                'b2sharebridge.View.depositList',
                array('filter' => 'pending')
            ),
        ];
        $entries[] = [
            'id' => 'published',
            'name' => 'Published Deposits',
            'url' => $this->URLGenerator->linkToRoute(
                'b2sharebridge.View.depositList',
                array('filter' => 'published')
            ),
        ];
        $entries[] = [
            'id' => 'failed',
            'name' => 'Failed Deposits',
            'url' => $this->URLGenerator->linkToRoute(
                'b2sharebridge.View.depositList',
                array('filter' => 'failed')
            ),
        ];
        return $entries;
    }
}
