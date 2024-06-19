const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const WooDependencyExtractionWebpackPlugin = require('@woocommerce/dependency-extraction-webpack-plugin');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const RemoveEmptyScriptsPlugin = require('webpack-remove-empty-scripts');

module.exports = {
	...defaultConfig,
	entry: {
		...defaultConfig.entry,
		'js/frontend/booking-form': './src/js/booking-form.js', // prettier-ignore
		'js/admin/writepanel': './src/js/writepanel.js', // prettier-ignore
		'css/frontend': './src/css/frontend.scss', // prettier-ignore
	},
	plugins: [
		...defaultConfig.plugins.filter(
			(plugin) =>
				plugin.constructor.name !== 'DependencyExtractionWebpackPlugin'
		),
		new WooDependencyExtractionWebpackPlugin(),
		new MiniCssExtractPlugin({
			filename: `[name].css`,
		}),
		new RemoveEmptyScriptsPlugin({
			stage: RemoveEmptyScriptsPlugin.STAGE_AFTER_PROCESS_PLUGINS,
		}),
	],
};
