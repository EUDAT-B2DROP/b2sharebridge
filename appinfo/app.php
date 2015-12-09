<?php
/**
 * ownCloud - b2sharebridge
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the LICENSE file.
 *
 * @author EUDAT <b2drop-devel@postit.csc.fi>
 * @copyright EUDAT 2015
 */

namespace OCA\B2shareBridge\AppInfo;

use OCP\App;
use OCP\Util;

$app = new Application();
$c = $app->getContainer();

$navigationEntry = function () use ($c) {
    return [
        'id' => $c->getAppName(),
        'order' => 100,
        'name' => $c->query('EudatL10N')->t('B2SHARE'),
        'href' => $c->getServer()->getURLGenerator()->linkToRoute('b2sharebridge.B2shareBridge.index'),
        'icon' => $c->getServer()->getURLGenerator()->imagePath('b2sharebridge', 'appbrowsericon.svg'),
    ];
};

$c->getServer()->getNavigationManager()->add($navigationEntry);

App::registerAdmin('b2sharebridge', 'lib/settings-admin');
App::registerPersonal('b2sharebridge', 'lib/settings-personal');
Util::addScript('b2sharebridge', 'fileactions');