/**
 * WordPress Dependencies
 */
const defaultConfig = require( '@wordpress/scripts/config/webpack.config.js' );

module.exports = {
	// Default wordpress config
	...defaultConfig,

	// custom config to avoid errors with lib-font dependency
	...{
		resolve: {
			fallback: {
				zlib: false,
				fs: false,
			},
		},
	},
};
