const Encore = require('@symfony/webpack-encore');
const webpack = require('webpack');

// Manually configure the runtime environment if not already configured yet by the "encore" command.
// It's useful when you use tools that rely on webpack.config.js file.
if (!Encore.isRuntimeEnvironmentConfigured()) {
    Encore.configureRuntimeEnvironment(process.env.NODE_ENV || 'dev');
}

Encore
    // directory where compiled assets will be stored
    .setOutputPath('public/build/')
    // public path used by the web server to access the output path
    .setPublicPath('/build')
    // only needed for CDN's or sub-directory deploy
    //.setManifestKeyPrefix('build/')
    //.autoProvidejQuery()
    
    // enables the Symfony UX Stimulus bridge (used in assets/bootstrap.js)
    //.enableStimulusBridge('./assets/controllers.json')

    // When enabled, Webpack "splits" your files into smaller pieces for greater optimization.
    .splitEntryChunks()

    // will require an extra script tag for runtime.js
    // but, you probably want this, unless you're building a single-page app
    .enableSingleRuntimeChunk()
    //.disableSingleRuntimeChunk()
    
    /*
     * FEATURE CONFIG
     *
     * Enable & configure other features below. For a full
     * list of features, see:
     * https://symfony.com/doc/current/frontend.html#adding-more-features
     */
    .cleanupOutputBeforeBuild()
    .enableBuildNotifications()
    .enableSourceMaps(!Encore.isProduction())
    // enables hashed filenames (e.g. app.abc123.css)
    .enableVersioning(Encore.isProduction())

    .configureBabel((config) => {
        config.plugins.push('@babel/plugin-proposal-class-properties');
        config.plugins.push('@babel/plugin-transform-runtime');
    })

    // enables @babel/preset-env polyfills
    .configureBabelPresetEnv((config) => {
        config.useBuiltIns = 'usage';
        config.corejs = 3;
    })

    // enables Sass/SCSS support
    .enableSassLoader()
    .enableVueLoader(() => {}, { runtimeCompilerBuild: true })
    // uncomment if you use TypeScript
    //.enableTypeScriptLoader()

    .autoProvideVariables({
             $: 'jquery',
             jQuery: 'jquery',
             'window.jQuery': 'jquery',
             CodeMirror: 'codemirror',
    })

    // uncomment to get integrity="..." attributes on your script & link tags
    // requires WebpackEncoreBundle 1.4 or higher
    .enableIntegrityHashes(Encore.isProduction())

    /*
     * ENTRY CONFIG
     *
     * Add 1 entry for each "page" of your app
     * (including one that's included on every page - e.g. "app")
     *
     * Each entry will result in one JavaScript file (e.g. app.js)
     * and one CSS file (e.g. app.css) if your JavaScript imports CSS.
     */
    //.enableReactPreset()
    .addEntry('app', './assets/app.js')
    //.addEntry('webfonts', './assets/js/webfonts.js')
    .addEntry('fileupload', './assets/js/fileupload.js')
    .addEntry('piechart', './assets/js/piechart.js')
    .addEntry('barchart', './assets/js/barchart.js')
    .addEntry('barchart_with_labels', './assets/js/barchart_with_labels.js')
    .addEntry('linechart', './assets/js/linechart.js')
    .addEntry('summernote', './assets/js/summernote.js')
    .addEntry('stockprices', './assets/js/stockprices.js')
    .addEntry('vue', './assets/vue/index.js')

    .addPlugin(new webpack.DefinePlugin({
        'ENV_API_ENDPOINT': JSON.stringify(process.env.API_ENDPOINT),
    }))
    // enable ESLint
    .addLoader({
        enforce: 'pre',
        test: /\.(js|vue)$/,
        loader: 'eslint-loader',
        exclude: /node_modules/,
        options: {
            fix: true,
            emitError: true,
            emitWarning: true,
        },
    })
;

module.exports = Encore.getWebpackConfig();
