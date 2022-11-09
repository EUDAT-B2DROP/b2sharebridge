# EUDAT B2SHARE integration app

[![Build](https://github.com/EUDAT-B2DROP/b2sharebridge/actions/workflows/build.yaml/badge.svg)](https://github.com/EUDAT-B2DROP/b2sharebridge/actions/workflows/build.yaml)

#### Table of Contents

1. [Plugin Description - What the plugin does provide](#module-description)
2. [Install - How to install the B2SHAREBRIDGE](#install)
3. [Development - Guide for contributing to the plugin](#development)
4. [Testing - Instructions to test the code](#testing)


## Module Description

This owncloud/nextcloud plugin allows the user to directly publish files from his personal cloud store to EUDAT B2SHARE.
The user interface of B2DROP is extended with a icon to publish files, and with a table view that shows the last published files.
If a user wants to publish a file, this transfer is done via the B2DROP server in the background.

## Install

1. on your B2DROP server go to the <owncloud/nextcloud>/apps directory
2. git clone this repository
3. enable the app on the B2DROP owncloud/nextcloud web interface
4. configure a B2SHARE endpoint in the B2DROP owncloud/nextcloud admin menu

## Development

There are no formal requirements to participate. If there are questions, feel free to contact the authors mentioned in AUTHORS.md

To work on the b2sharebridgetabview.js you need to use webpack and local jQuery installation.

## Testing

For testing your php code against styleguides you need to install PHP_CodeSniffer, then run:
```
phpcs --extensions=php --ignore=*/tests/*,*/templates/* .
```
For unit tests you need to hava a php interpreter and [PHPUnit](http://phpunit.de/getting-started.html).
These tests need a Nextcloud deployment, so execute:
```
export CORE_BRANCH=stable12
export BRIDGE_BRANCH=nextcloud12
export B2SHAREBRIDGE_LOCAL_PATH=
git clone https://github.com/nextcloud/core.git --recursive --depth 1 -b $CORE_BRANCH nextcloud
cd nextcloud
./occ maintenance:install --admin-user admin --admin-pass admin

#decide whether you want to use github code or your local developments:
if [[ "$B2SHAREBRIDGE_LOCAL_PATH" == '' ]]
then
    git clone https://github.com/EUDAT-B2DROP/b2sharebridge.git -b $BRIDGE_BRANCH apps/b2sharebridge
else
    rsync -uvaPr --delete  --exclude “.git*” --exclude ".idea" $B2SHAREBRIDGE_LOCAL_PATH/ apps/b2sharebridge
fi

./occ app:enable b2sharebridge

./occ app:check-code b2sharebridge
cd apps/b2sharebridge/tests
phpunit -c phpunit.xml
phpunit -c phpunit.integration.xml
```
You can afterwards also:
```
cd ../../..
php -S localhost:8080
```
And should be able to connect to [localhost:8080](http://localhost:8080) and see a working service.
