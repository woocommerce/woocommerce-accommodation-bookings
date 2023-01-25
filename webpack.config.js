const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const RemoveEmptyScriptsPlugin = require('webpack-remove-empty-scripts');
const path = require( 'path' );

module.exports = {
	...defaultConfig,
	entry: {
		'js/frontend/booking-form': '/assets/js/booking-form.js', // prettier-ignore
		'js/admin/writepanel': '/assets/js/writepanel.js', // prettier-ignore
		'css/frontend': '/assets/css/frontend.scss', // prettier-ignore
	},
	output: {
		path: path.resolve( __dirname, 'dist' ),
		filename: '[name].js',
	},
	plugins: [
		...defaultConfig.plugins,
		new RemoveEmptyScriptsPlugin()
	]
};
