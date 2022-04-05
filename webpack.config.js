
const path = require('path')

module.exports = {
    entry: path.join(__dirname, 'src', 'b2sharebridgetabview.js'),
    output: {
            path: path.resolve(__dirname, 'js'),
            publicPath: '/js/',
            filename: 'b2sharebridgetabview.js',
        },
    mode: 'production',
    externals: {
            jquery: 'jQuery'
        },
    optimization: {
            minimize: false
        },
}
