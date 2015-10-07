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
use \OCA\Eudat\Db\AuthorMapper;

$app = new App('eudat');
$container = $app->getContainer();


$container->query('OCP\INavigationManager')->add(function () use ($container) {
    $urlGenerator = $container->query('OCP\IURLGenerator');
    $l10n = $container->query('OCP\IL10N');
    return [
        // the string under which your app will be referenced in owncloud
        'id' => 'eudat',

        // sorting weight for the navigation. The higher the number, the higher
        // will it be listed in the navigation
        'order' => 100,

        // the route that will be shown on startup
        'href' => $urlGenerator->linkToRoute('eudat.page.index'),

        // the icon that will be shown in the navigation
        // this file needs to exist in img/
        'icon' => $urlGenerator->imagePath('eudat', 'app.svg'),

        // the title of your application. This will be used in the
        // navigation or on the settings page of your app
        'name' => $l10n->t('B2SHARE'),
    ];
});

// register classes
\OC::$CLASSPATH['OCA\Eudat\Transfer'] = 'eudat/lib/transfer.php';

\OCP\App::registerAdmin('eudat', 'settings');
\OCP\Util::addScript('eudat', 'fileactions');
\OCP\Util::addScript('eudat','b2sharebridge');
\OCP\Util::addStyle( 'eudat','b2sharebridge');

