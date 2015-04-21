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


OCP\App::registerAdmin('eudat', 'settings');

OCP\APP::register(array(
	// the string under which your app will be referenced in owncloud
    'id' => 'eudat',

    // sorting weight for the navigation. The higher the number, the higher
    // will it be listed in the navigation
    'order' => 10,
    
    // the title of your application. This will be used in the
    // navigation or on the settings page of your app
    'name' => \OC_L10N::get('eudat')->t('Eudat')
));
OCP\Util::addscript('eudat','b2sharebridge');
OCP\Util::addStyle( 'eudat','style');
