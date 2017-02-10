# EUDAT B2SHARE integration app

[![Build Status](https://travis-ci.org/EUDAT-B2DROP/b2sharebridge.svg?branch=master)](https://travis-ci.org/EUDAT-B2DROP/b2sharebridge)

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

## Testing

For testing your php code you need to [install PHPUnit](http://phpunit.de/getting-started.html) and PHP_CodeSniffer, then run:

    phpunit -c phpunit.xml
    phpcs --extensions=php --ignore=*/tests/*,*/templates/* .

Another way to directly test your code with your browser locally, when you have a php interpreter:

```
export CORE_BRANCH=stable11;
export BRIDGE_BRANCH=nextcloud11;
export B2SHAREBRIDGE=<local path>
git clone https://github.com/nextcloud/core.git --recursive --depth 1 -b $CORE_BRANCH nextcloud
cd nextcloud
./occ maintenance:install --admin-user admin --admin-pass admin

#decide whether you want to use github code or your local developments:
git clone https://github.com/EUDAT-B2DROP/b2sharebridge.git -b $BRIDGE_BRANCH apps/b2sharebridge
#rsync -uvaPr --delete  --exclude “.git*” --exclude ".idea" $B2SHAREBRIDGE/ apps/b2sharebridge
./occ app:enable b2sharebridge

php -S localhost:8080
```

You should be able to connect to [localhost:8080](http://localhost:8080) and see a working service
