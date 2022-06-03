const path = require('path')
const webpack = require("webpack");

module.exports = {
    entry: path.join(__dirname, 'src', 'b2sharebridgetabview.js'),
    output: {
        path: path.resolve(__dirname, 'js'),
        publicPath: '/js/',
        filename: 'b2sharebridgetabview.js',
    },
    mode: 'production',
    plugins: [
        new webpack.ProvidePlugin({
            $: "jquery",
            jQuery: "jquery"
        })
    ],
    optimization: {
        minimize: false
    },
}
