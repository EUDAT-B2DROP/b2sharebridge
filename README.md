# EUDAT B2SHARE integration app

[![Build Status](https://travis-ci.org/EUDAT-B2DROP/b2sharebridge.svg?branch=master)](https://travis-ci.org/EUDAT-B2DROP/b2sharebridge)

#### Table of Contents

1. [Plugin Description - What the plugin does provide](#module-description)
2. [Install - How to install the B2SHAREBRIDGE](#install)
3. [Development - Guide for contributing to the plugin](#development)
4. [Testing - Instructions to test the code](#testing)


## Module Description

This owncloud plugin allows the user to directly publish files from his personal cloud store to EUDAT B2SHARE.
The user interface of B2DROP is extended with a icon to publish files, and with a table view that shows the last published files.
If a user wants to publish a file, this transfer is done via the B2DROP server in the background.

## Install

1. on your B2DROP server go to the <owncloud>/core/apps directory
2. git clone this repository
3. enable the app on the B2DROP/owncloud web interface
4. configure a B2SHARE endpoint in the B2DROP/owncloud admin menu

## Development

There are no formal requirements to participate. If there are questions, feel free to contact the authors mentioned in AUTHORS.md

## Testing

For testing your php code you need to [install PHPUnit](http://phpunit.de/getting-started.html) and run:

    phpunit -c phpunit.xml

Another way to directly test your code with your browser is via "ocdev". This is a tool provided by the owncloud developers, it requires python3 (virtualenv is suggested) and php. Some but not all instructions:

```
pip install ocdev
BRANCH=stable9
B2SHAREBRIDGE=<YOUR_LOCAL_REPO>

ocdev setup core --dir owncloud --branch $BRANCH --no-history

rsync -uvaPr --delete  --exclude “.git*” --exclude ".idea" $B2SHAREBRIDGE/ owncloud/core/apps/b2sharebridge
cd owncloud
./occ maintenance:install --admin-user admin --admin-pass admin
./occ app:enable b2sharebridge
ocdev server
```

You should be able to connect to [localhost:8080](http://localhost:8080) and see a working service
