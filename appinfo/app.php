<?php
/**
 * ownCloud - eudat
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the LICENSE file.
 *
 * @author EUDAT <b2drop-devel@postit.csc.fi>
 * @copyright EUDAT 2015
 */

namespace OCA\Eudat\AppInfo;

use OCP\App;
use OCP\Util;

$app = new Application();
$c = $app->getContainer();

$navigationEntry = function () use ($c) {
    return [
        'id' => $c->getAppName(),
        'order' => 100,
        'name' => $c->query('EudatL10N')->t('B2SHARE'),
        'href' => $c->getServer()->getURLGenerator()->linkToRoute('eudat.Eudat.index'),
        'icon' => $c->getServer()->getURLGenerator()->imagePath('eudat', 'app.svg'),
    ];
};

$c->getServer()->getNavigationManager()->add($navigationEntry);

App::registerAdmin('eudat', 'lib/settings');
Util::addScript('eudat', 'fileactions');