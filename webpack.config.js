const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
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
	module: {
		...defaultConfig.module,
		rules: [
			...defaultConfig.module.rules,
			{
				test: /\.s[ac]ss$/i,
				exclude: /node_modules/,
				use: ["sass-loader",],
			},
		]
	}
};
