const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const path = require('path');

// Preserve wp-scripts' block discovery (src/<block>/block.json) and add the
// standalone editor sidebar entry → build/js/editor.js.
module.exports = {
	...defaultConfig,
	entry: {
		...(typeof defaultConfig.entry === 'function'
			? defaultConfig.entry()
			: defaultConfig.entry),
		'js/editor': path.resolve(__dirname, 'src/js/editor/index.js'),
		'js/breadcrumbs': path.resolve(
			__dirname,
			'src/js/blocks/breadcrumbs/index.js'
		),
	},
};
