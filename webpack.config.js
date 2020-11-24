var Encore = require('@symfony/webpack-encore');

Encore
    .setOutputPath('web/build/')
    .setPublicPath('/build')
    .cleanupOutputBeforeBuild()
    // Convert typescript files.
    .enableTypeScriptLoader()
    .enableLessLoader()
    .addStyleEntry('global', [
        './public/scss/application.scss',
        './vendor/surfnet/stepup-bundle/src/Resources/public/less/stepup.less'
    ])
    .addEntry('submitonload', './public/typescript/submitonload.ts')
    .addEntry('app', './public/typescript/app.ts')

    // Convert sass files.
    .enableSassLoader(function (options) {
        options.sassOptions = {
            outputStyle: 'expanded',
            includePaths: ['public'],
        };
    })
    .addLoader({test: /\.scss$/, loader: 'import-glob-loader'})
    .addLoader({
        test: /\.tsx?|\.js$/,
        exclude: /node_modules|vendor/,
        use: [{
            loader: 'tslint-loader',
            options: {
                configFile: 'tslint.json',
                emitErrors: true,
                failOnHint: Encore.isProduction(),
                typeCheck: true
            }
        }]
    })
    .enableSingleRuntimeChunk()
    .enableSourceMaps(!Encore.isProduction())
    // enables hashed filenames (e.g. app.abc123.css)
    .enableVersioning(Encore.isProduction())
;


module.exports = Encore.getWebpackConfig();
