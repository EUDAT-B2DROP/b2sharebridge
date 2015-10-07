<?php
/**
 * ownCloud - eudat
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author EUDAT <b2drop-devel@postit.csc.fi>
 * @copyright EUDAT 2015
 */

namespace OCA\Eudat\AppInfo;

use OCP\AppFramework\App;
use OCP\Util;

$app = new App('eudat');
$container = $app->getContainer();
$container->query('OCP\INavigationManager')->add(function () use ($container) {
    $urlGenerator = $container->query('OCP\IURLGenerator');
    $l10n = $container->query('OCP\IL10N');
    return [
        'id' => 'eudat',
        'order' => 100,
        'href' => $urlGenerator->linkToRoute('eudat.page.index'),
        'icon' => $urlGenerator->imagePath('eudat', 'app.svg'),
        'name' => $l10n->t('B2SHARE'),
    ];
});

// register classes
\OC::$CLASSPATH['OCA\Eudat\Transfer'] = 'eudat/lib/transfer.php';

\OCP\App::registerAdmin('eudat', 'settings');
Util::addScript('eudat', 'fileactions');
Util::addScript('eudat','b2sharebridge');
Util::addStyle( 'eudat','b2sharebridge');

