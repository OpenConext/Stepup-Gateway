const Encore = require('@symfony/webpack-encore');
const ESLintPlugin = require('eslint-webpack-plugin');
const path = require('path');

if (!Encore.isRuntimeEnvironmentConfigured()) {
    Encore.configureRuntimeEnvironment(process.env.NODE_ENV || 'development');
}

Encore.configureBabelPresetEnv(config => {
    config.useBuiltIns = 'usage';
    config.corejs = 3;
    config.targets = { ie: '11' };
});

Encore
    .setOutputPath('public/build/')
    .setPublicPath('/build')
    .cleanupOutputBeforeBuild()
    .enableTypeScriptLoader()
    .enableLessLoader()
    .enableSassLoader(options => {
        options.api = 'modern';
        options.sassOptions = {
            outputStyle: 'expanded',
            includePaths: ['public'],
            silenceDeprecations: ["import", "color-functions", "global-builtin"]
        };
        options.webpackImporter = false;
    })
    .enablePostCssLoader()

    .addStyleEntry('global', [
        './public/scss/application.scss',
        './vendor/surfnet/stepup-bundle/src/Resources/public/less/stepup.less'
    ])
    .addEntry('submitonload', './public/typescript/submitonload.ts')
    .addEntry('app', './public/typescript/app.ts')

    .addLoader({ test: /\.scss$/, loader: 'webpack-import-glob-loader' })

    .addPlugin(new ESLintPlugin({
        extensions: ['ts', 'js'],
        files: 'public/typescript',
        emitWarning: true,
        failOnError: Encore.isProduction(),
        context: path.resolve(__dirname)
    }))

    .enableSingleRuntimeChunk()
    .enableSourceMaps(!Encore.isProduction())
    .enableVersioning(Encore.isProduction())
;

module.exports = Encore.getWebpackConfig();
