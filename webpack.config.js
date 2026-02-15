<<<<<<< HEAD
const path = require('path');
const webpack = require('webpack');
const MiniCssExtractPlugin = require("mini-css-extract-plugin");

module.exports = {
    mode: 'production',
    devtool: false,

    // 1. Entry Points
    entry: {
        // [UPDATED] Sidebar: Include JS + SCSS
        sidebar: [
            path.join(__dirname, 'src', 'components', 'sidebar', 'sidebar.js'), // Add this
            path.join(__dirname, 'src', 'components', 'sidebar', 'sidebar.scss')
        ],

        // Calendar: Already correct (Has JS + SCSS)
        calendar: [
            path.join(__dirname, 'src', 'pages', 'calendar', 'calendar.js'),
            path.join(__dirname, 'src', 'pages', 'calendar', 'calendar.scss')
        ],

        // [UPDATED] Timesheet: Include JS + SCSS
        timesheet: [
            path.join(__dirname, 'src', 'pages', 'timesheet', 'timesheet.js'), // Add this
            path.join(__dirname, 'src', 'pages', 'timesheet', 'timesheet.scss')
        ]
    },

    // 2. Output
    output: {
        path: path.resolve(__dirname, 'js'),
        filename: '[name].js', 
        chunkFilename: 'chunks/[name]-[chunkhash].js',
        clean: true
    },

    // 3. Module Rules
    module: {
        rules: [
            {
                test: /\.m?js$/,
                exclude: /node_modules/,
                resolve: { fullySpecified: false },
                use: {
                    loader: 'babel-loader',
                    options: { presets: ['@babel/preset-env'] }
                }
            },
            {
                test: /\.s[ac]ss$/i,
                use: [
                    MiniCssExtractPlugin.loader, 
                    'css-loader',
                    {
                        loader: 'sass-loader',
                        options: {
                            sassOptions: {
                                silenceDeprecations: ['import'],
                            },
                        },
                    },
                ],
            },
            {
                test: /\.css$/i,
                use: [MiniCssExtractPlugin.loader, 'css-loader'],
            },
            {
                test: /\.(png|jpg|gif|svg|woff|woff2|eot|ttf|otf)$/,
                type: 'asset/resource',
                generator: { filename: '../img/[name][ext][query]' }
            }
        ]
    },

    // 4. Plugins
    plugins: [
        new webpack.ProvidePlugin({
            process: 'process/browser',
            Buffer: ['buffer', 'Buffer'],
        }),
        
        new MiniCssExtractPlugin({
            filename: "../css/[name].css", // produces sidebar.css, calendar.css, timesheet.css
        }),
    ],

    // 5. Resolution
    resolve: {
        extensions: ['.js', '.json', '.scss', '.css'],
        alias: {
            'src': path.resolve(__dirname, 'src/'), 
            'process/browser': require.resolve('process/browser.js'), 
        },
        fallback: {
            "buffer": require.resolve("buffer/"),
            "process": require.resolve("process/browser.js"),
        }
    },
    
    optimization: {
        concatenateModules: false,
    },
=======
const path = require('path');
const webpack = require('webpack');
const MiniCssExtractPlugin = require("mini-css-extract-plugin");

module.exports = {
    mode: 'production',
    devtool: false,

    // 1. Entry Points
    entry: {
        // [UPDATED] Sidebar: Include JS + SCSS
        sidebar: [
            path.join(__dirname, 'src', 'components', 'sidebar', 'sidebar.js'), // Add this
            path.join(__dirname, 'src', 'components', 'sidebar', 'sidebar.scss')
        ],

        // Calendar: Already correct (Has JS + SCSS)
        calendar: [
            path.join(__dirname, 'src', 'pages', 'calendar', 'calendar.js'),
            path.join(__dirname, 'src', 'pages', 'calendar', 'calendar.scss')
        ],

        // [UPDATED] Timesheet: Include JS + SCSS
        timesheet: [
            path.join(__dirname, 'src', 'pages', 'timesheet', 'timesheet.js'), // Add this
            path.join(__dirname, 'src', 'pages', 'timesheet', 'timesheet.scss')
        ]
    },

    // 2. Output
    output: {
        path: path.resolve(__dirname, 'js'),
        filename: '[name].js', 
        chunkFilename: 'chunks/[name]-[chunkhash].js',
        clean: true
    },

    // 3. Module Rules
    module: {
        rules: [
            {
                test: /\.m?js$/,
                exclude: /node_modules/,
                resolve: { fullySpecified: false },
                use: {
                    loader: 'babel-loader',
                    options: { presets: ['@babel/preset-env'] }
                }
            },
            {
                test: /\.s[ac]ss$/i,
                use: [
                    MiniCssExtractPlugin.loader, 
                    'css-loader',
                    {
                        loader: 'sass-loader',
                        options: {
                            sassOptions: {
                                silenceDeprecations: ['import'],
                            },
                        },
                    },
                ],
            },
            {
                test: /\.css$/i,
                use: [MiniCssExtractPlugin.loader, 'css-loader'],
            },
            {
                test: /\.(png|jpg|gif|svg|woff|woff2|eot|ttf|otf)$/,
                type: 'asset/resource',
                generator: { filename: '../img/[name][ext][query]' }
            }
        ]
    },

    // 4. Plugins
    plugins: [
        new webpack.ProvidePlugin({
            process: 'process/browser',
            Buffer: ['buffer', 'Buffer'],
        }),
        
        new MiniCssExtractPlugin({
            filename: "../css/[name].css", // produces sidebar.css, calendar.css, timesheet.css
        }),
    ],

    // 5. Resolution
    resolve: {
        extensions: ['.js', '.json', '.scss', '.css'],
        alias: {
            'src': path.resolve(__dirname, 'src/'), 
            'process/browser': require.resolve('process/browser.js'), 
        },
        fallback: {
            "buffer": require.resolve("buffer/"),
            "process": require.resolve("process/browser.js"),
        }
    },
    
    optimization: {
        concatenateModules: false,
    },
>>>>>>> 6edf4541514ac05df3faedfa2f961d65a5495869
};