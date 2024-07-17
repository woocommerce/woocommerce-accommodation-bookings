module.exports = {
	extends: ['plugin:@woocommerce/eslint-plugin/recommended'],
	globals: {
		wp: false,
	},
	rules: {
		'@wordpress/no-unused-vars-before-return': 0,
		'@woocommerce/dependency-group': 0,
		camelcase: 0,
		'import/no-extraneous-dependencies': 0,
		'import/no-unresolved': 0,
		'import/named': 0,
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
	ignorePatterns: ['build'],
};
