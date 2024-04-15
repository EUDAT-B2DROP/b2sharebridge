# EUDAT B2SHARE integration app

[![Build](https://github.com/EUDAT-B2DROP/b2sharebridge/actions/workflows/build.yaml/badge.svg?branch=master)](https://github.com/EUDAT-B2DROP/b2sharebridge/actions/workflows/build.yaml)

#### Table of Contents

1. [Plugin Description - What the plugin does provide](#module-description)
2. [Install - How to install the B2SHAREBRIDGE](#install)
3. [Development - Guide for contributing to the plugin](#development)
    1. [Frontend development - Guide for frontend building](#frontend-development)
    2. [Testing - Instructions to test the code](#testing)
    3. [Styling - Instructions to check the code quality](#style-and-style-fix)

## Module Description

This owncloud/nextcloud plugin allows the user to directly publish files from his personal cloud store to EUDAT B2SHARE.
The user interface of B2DROP is extended with an icon to publish files, and with a table view that shows the last
published files.
If a user wants to publish a file, this transfer is done via the B2DROP server in the background.

## Install

- on your B2DROP server go to the <owncloud/nextcloud>/apps directory

```console
cd nextcloud/apps
```

- git clone this repository

```console
git clone https://github.com/EUDAT-B2DROP/b2sharebridge.git b2sharebridge
```

- then install the dependencies using:

```console
make composer
```

- enable the app on the B2DROP owncloud/nextcloud web interface or in the console with:

```console
sudo -u <www-data/your webserver user> php occ app:enable b2sharebridge
```

- configure a B2SHARE endpoint in the B2DROP owncloud/nextcloud **admin** menu

## Development

There are no formal requirements to participate. If there are questions, feel free to contact the authors mentioned in
AUTHORS.md

### Frontend development

The app is implemented with [Vue.js](https://vuejs.org/). Build the frontend code after doing changes to its source
in `src/` which requires to have Node and npm installed:

- :woman_technologist: Run `make dev-setup` to install the frontend dependencies
- :building_construction: To build the Javascript whenever you make changes, run `make build-js`

To continuously run the build when editing source files you can make use of the `make watch-js` command.

### Testing

In order to run tests you **have to**
[install nextcloud](https://docs.nextcloud.com/server/latest/admin_manual/installation/index.html).

#### Unit testing

Make sure you have the dependencies installed with `make composer`.

> **warning**
>
> If you don't put the b2sharebridge-app at `nextcloud/apps` you need to
> - Set the `NEXTCLOUD_ROOT` variable to your nextcloud directory, for
    example `export NEXTCLOUD_ROOT=/var/www/nextcloud`

You can now run all tests with `make test`, which runs unit **and** integration tests.

#### Manual (Frontend-) Testing
For manual testing you need to [install the b2sharebridge-app](#install) and enable it in the nextcloud server.

> **warning**
>
> If you don't put the b2sharebridge-app at `nextcloud/apps` you need to
> - Create a softlink at `nextcloud/apps` with `ls -s <your dev directory> b2sharebridge` in order to install the app
> - You may also need to change the owner of the app-files
    with `sudo chown -R <webserver-user:webserver-group> </path/to/your/>b2sharebridge`. Alternatively set the user of
    your webserver to your own user. This is **NOT** recommended for your production system!

### Style and style-fix

In order to keep the quality of the app higher there are options to automatically check your files for styling issues
and other code smells:

- `make phplint` to show and `make phplint-fix` to automatically fix some php issues
- `make stylelint` to show and `make stylelint-fix` to automatically fix some css, scss or vue issues
- `make lint` to show and `make lint-fix` to automatically fix some javascript issues

## Additional Notes
You need to at least use charset `utf8mb4` (or similar) in your database, because the fetched communitites contain the `🔒`-emoji in their name. Otherwise this causes mysql (or similar) to show a question mark `?` instead. This bug only causes visual issues.
