const webpackConfig = require('@nextcloud/webpack-vue-config');
const path = require('path')

// Preserve all entry points required for Nextcloud app to function properly
webpackConfig.entry = {
    main: path.resolve(path.join('src', 'main.js')),
    filetabmain: path.resolve(path.join('src', 'filetab-main.js')),
    settingsadmin: path.resolve(path.join('src', 'settings-admin.js')),
    settingspersonal: path.resolve(path.join('src', 'settings-personal.js'))
}

// Improve chunking configuration
webpackConfig.optimization = {
    ...webpackConfig.optimization,
    splitChunks: {
        chunks: "all",
        maxInitialRequests: 5,
        maxAsyncRequests: 5,
        cacheGroups: {
            // Default vendors group
            defaultVendors: {
                test: /[\\/]node_modules[\\/]/,
                priority: -10,
                reuseExistingChunk: true,
                name: "vendors",
                // we can't split this up further, because we need to explicitly name every chunk
                //maxSize: 200000,
            },
            // Default group
            default: {
                minChunks: 2,
                priority: -20,
                reuseExistingChunk: true,
            },
        },
    },
    // Enable runtime chunk splitting
    //runtimeChunk: 'single',
};

module.exports = webpackConfig
