const webpackConfig = require('@nextcloud/webpack-vue-config');
const path = require('path')

webpackConfig.entry = {
    main: path.resolve(path.join('src', 'main.js')),
    filetabmain: path.resolve(path.join('src', 'filetab-main.js')),
    settingsadmin: path.resolve(path.join('src', 'settings-admin.js')),
    settingspersonal: path.resolve(path.join('src', 'settings-personal.js'))
}

module.exports = webpackConfig
