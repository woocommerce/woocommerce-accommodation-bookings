module.exports = {
	extends: ['plugin:@woocommerce/eslint-plugin/recommended'],
	globals: {
		wp: false,
	},
	rules: {
		'@woocommerce/dependency-group': 'off',
		'react/react-in-jsx-scope': 'off',
	},
	settings: {
		jsdoc: { mode: 'typescript' },
		// List of modules that are externals in our webpack config.
		// This helps the `import/no-extraneous-dependencies` and
		//`import/no-unresolved` rules account for them.
		'import/core-modules': [
			'@woocommerce/blocks-registry',
			'@woocommerce/settings',
			'@wordpress/i18n',
			'@wordpress/element',
			'@wordpress/html-entities',
		],
	},
};
