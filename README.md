# EUDAT B2SHARE integration app

[![Build](https://github.com/EUDAT-B2DROP/b2sharebridge/actions/workflows/build.yaml/badge.svg?branch=master)](https://github.com/EUDAT-B2DROP/b2sharebridge/actions/workflows/build.yaml)

#### Table of Contents

1. [Plugin Description - What the plugin does provide](#module-description)
2. [Install - How to install the B2SHAREBRIDGE](#install)
3. [OIDC Login Tokens - Publishing without a manual API token](#oidc-login-tokens)
4. [Development - Guide for contributing to the plugin](#development)
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

## OIDC Login Tokens

Instead of copying a B2SHARE API token into the personal settings, users that log into Nextcloud via OpenID Connect
([user_oidc](https://github.com/nextcloud/user_oidc)) can publish with an OIDC token directly. If a valid OIDC token
is available, it is used against the B2SHARE (InvenioRDM) API and no manual token is required. Manually configured
API tokens keep working as a fallback and are used whenever no (valid) OIDC token is available.

Two token sources are supported (in this order):

1. **Internal token** (recommended): a fresh token minted by the Nextcloud
   [OIDC Identity Provider app](https://apps.nextcloud.com/apps/oidc) ("oidc", >= 1.4.0, with Nextcloud acting as the
   OpenID Connect provider) for a target audience. This does **not** require user_oidc's `store_login_token` setting.
2. **External login token**: the login token of the external OpenID Connect provider that was used to log into
   Nextcloud, stored and refreshed by user_oidc.

### Prerequisites

- The `user_oidc` app is installed
- **For internal tokens:** the "oidc" identity provider app is installed and enabled, a client is created for the
  B2SHARE audience, and the audience (client id) is configured for this app:

  ```console
  sudo -u <www-data/your webserver user> php occ config:app:set b2sharebridge oidc_audience --value=<client-id>
  ```

- **For external login tokens:** users log into Nextcloud via an OpenID Connect provider configured in user_oidc, and
  user_oidc must store the login token:

  ```console
  sudo -u <www-data/your webserver user> php occ config:app:set user_oidc store_login_token --value=1
  ```

- The B2SHARE server (version **3** / InvenioRDM endpoints) must accept those access tokens. By default InvenioRDM
  only accepts its own tokens, so it has to be configured to validate the JWTs of the corresponding issuer (the
  Nextcloud "oidc" app for internal tokens, or the external provider for login tokens), e.g. via a custom
  `OAUTH2SERVER_JWT_VERIFICATION_FACTORY` that verifies against the issuer's JWKS. The token `sub` claim must map to
  an existing B2SHARE account.

### Limitations

- OIDC tokens are only available within logged-in web requests (external login tokens are stored in the user session,
  internal tokens require an authenticated user). Background jobs cannot obtain or refresh them: uploads above
  250 MB are queued with a snapshot of the token and may
  fail if it expires before the job runs. Simply retry the upload in that case.
- OIDC login tokens are only used for servers configured as version 3 (InvenioRDM). Legacy version 2 servers keep
  using manually configured API tokens (long JWTs are not usable as URL query parameters).
- The app has no hard dependency on user_oidc; without it (or without `store_login_token`) it behaves exactly as before.

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
