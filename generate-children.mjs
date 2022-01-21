import { promises as fs, constants } from 'fs';
import fsExtra from 'fs-extra';

// Used to track the sources for each theme.
let sources = [];

const getThemeDir = ( slug ) => {
	return '../themes/' + slug;
}

const fontNameConventions = [
	{
		"name": "Small",
		"slug": "small"
	},
	{
		"name": "Medium",
		"slug": "medium"
	},
	{
		"name": "Large",
		"slug": "large"
	},
	{
		"name": "Extra Large",
		"slug": "x-large"
	}
];

(async function start() {
	let args = process.argv.slice(2);
	return await generateChildren();
})();

function getBlockPatternsPhp( childTheme, patterns ) {
	return `<?php
/**
 * ${childTheme.name} Theme: Block Patterns
 *
 * @package ${childTheme.name}
 * @since   1.0.0
 */
if ( ! function_exists( '${childTheme.slug}_register_block_patterns' ) ) :

	function ${childTheme.slug}_register_block_patterns() {

		if ( function_exists( 'register_block_pattern_category' ) ) {
			register_block_pattern_category(
				'${childTheme.slug}',
				array( 'label' => __( '${childTheme.name}', '${childTheme.slug}' ) )
			);
		}

		if ( function_exists( 'register_block_pattern' ) ) {
			$block_patterns = array( ${patterns} );

			foreach ( $block_patterns as $block_pattern ) {
				register_block_pattern(
					'${childTheme.slug}/' . $block_pattern,
					require __DIR__ . '/patterns/' . $block_pattern . '.php'
				);
			}
		}
	}
endif;

add_action( 'init', '${childTheme.slug}_register_block_patterns', 9 );`

}

function getReadmeTxt ( childTheme ) {
	return `=== ${childTheme.name} ===
Contributors: Automattic
Requires at least: 5.8
Tested up to: 5.8
Requires PHP: 5.7
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

== Description ==

${childTheme.descriptionLong}

== Changelog ==

= 1.0.0 =
* Initial release

== Copyright ==

${childTheme.name} WordPress Theme, (C) 2021 Automattic, Inc.
${childTheme.name} is distributed under the terms of the GNU GPL.

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

${childTheme.name} uses the following resources:

License: CC0
${sources.join('')}
Included in theme screenshot and in block patterns.`;

}

function getPackageJson( childTheme ) {
return `{
  "name": "${childTheme.slug}",
  "version": "${childTheme.version}",
  "description": "${childTheme.description}",
  "bugs": {
    "url": "https://github.com/Automattic/themes/issues/new?label=[Theme]%20${childTheme.name}"
  },
  "homepage": "https://github.com/Automattic/themes/blob/trunk/${childTheme.slug}",
  "devDependencies": {
    "@wordpress/base-styles": "^4.0.4",
    "@wordpress/browserslist-config": "^4.1.0",
    "autoprefixer": "^10.4.2",
    "chokidar-cli": "^3.0.0",
    "postcss": "^8.2.12",
    "postcss-cli": "^9.1.0",
    "sass": "^1.47.0"
  },
  "browserslist": [
    "extends @wordpress/browserslist-config"
  ],
  "scripts": {
    "start": "chokidar \\"**/*.scss\\" -c \\"npm run build\\" --initial",
    "build": "npm run build:scss",
    "build:scss": "sass sass/theme.scss assets/theme.css --style=expanded && postcss assets/theme.css --use autoprefixer --output assets/theme.css --map"
  },
  "author": "Automattic",
  "license": "GPLv2"
}`
}

async function getStyleCss( directory ) {
    return await fs.readFile( directory + '/style.css', 'utf8' );
}

async function getThemeJson( directory ) {
    const themeJsonString = await fs.readFile( directory + '/theme.json', 'utf8' );
    return JSON.parse( themeJsonString );
}

async function getThemes() {
    const themesJsonString = await fs.readFile( 'themes.json', 'utf8' );
    return JSON.parse( themesJsonString );
}

async function getFonts() {
    const fontsJsonString = await fs.readFile( 'fonts.json', 'utf8' );
    return JSON.parse( fontsJsonString );
}

async function getPalettes() {
    const palettesJsonString = await fs.readFile( 'palettes.json', 'utf8' );
    return JSON.parse( palettesJsonString );
}

async function generatePackageJson( childTheme ) {
	const themeDir = getThemeDir( childTheme.slug );
	const themePackageJson = getPackageJson( childTheme );
	await fs.writeFile( themeDir + '/package.json', themePackageJson )
}

const capitalize = string => string && string[0].toUpperCase() + string.slice(1);

async function generateThemeJson( childTheme ) {
	const themeDir = getThemeDir( childTheme.slug );
	if ( ! childTheme.themeJson ) {
		return;
	}

	const themeJson = {
		"$schema": "https://json.schemastore.org/theme-v1.json",
		"version": 1,
	}

	if ( childTheme.themeJson.settings ) {
		themeJson.settings = {};
	};

	if ( childTheme.themeJson.settings.color ) {
		themeJson.settings.color = {};

		if ( childTheme.themeJson.settings.color.palette ) {
			const palette = childTheme.themeJson.settings.color.palette;
			const colors = Object.keys( palette );
			themeJson.settings.color.palette = colors.map( color => ( {
				"slug": color,
				"color": palette[ color ],
				"name": capitalize( color )
			} ) );
		}
	};

	if ( childTheme.themeJson.settings.custom ) {
		themeJson.settings.custom = childTheme.themeJson.settings.custom;

		themeJson.settings.custom.color = {};
		if ( childTheme.themeJson.settings.color.palette.foreground ) {
			themeJson.settings.custom.color.foreground = "var(--wp--preset--color--foreground)";
		} else {
			themeJson.settings.custom.color.foreground = "var(--wp--preset--color--primary)";
		}

		if ( childTheme.themeJson.settings.color.palette.background ) {
			themeJson.settings.custom.color.background = "var(--wp--preset--color--background)";
		}

		if ( childTheme.themeJson.settings.color.palette.primary ) {
			themeJson.settings.custom.color.primary = "var(--wp--preset--color--primary)";
		} else {
			themeJson.settings.custom.color.primary = "var(--wp--preset--color--foreground)";
		}

		if ( childTheme.themeJson.settings.color.palette.secondary ) {
			themeJson.settings.custom.color.secondary = "var(--wp--preset--color--secondary)";
		} else {
			themeJson.settings.custom.color.secondary = "var(--wp--preset--color--foreground)";
		}

		if ( childTheme.themeJson.settings.color.palette.tertiary ) {
			themeJson.settings.custom.color.tertiary = "var(--wp--preset--color--tertiary)";
		} else {
			themeJson.settings.custom.color.tertiary = "var(--wp--preset--color--background)";
		}

		themeJson.settings.custom.color = {
			"foreground": "var(--wp--preset--color--foreground)",
			"background": "var(--wp--preset--color--background)",
			"primary": "var(--wp--preset--color--foreground)",
			"secondary": "var(--wp--preset--color--foreground)",
			"tertiary": "var(--wp--preset--color--tertiary)",
		}
	};

	if ( childTheme.themeJson.settings.color && childTheme.themeJson.settings.color.colorPalettes ) {
		const colorPalettes = childTheme.themeJson.settings.color.colorPalettes;
		const allPalettes = await getPalettes();
		themeJson.settings.custom.colorPalettes = colorPalettes.map( palette => {
			return allPalettes[ palette ];
		} );
	}

	if ( childTheme.themeJson.settings.layout ) {
		themeJson.settings.layout = childTheme.themeJson.settings.layout;
	}

	if ( childTheme.themeJson.settings.typography ) {
		themeJson.settings.typography = {};

		if ( childTheme.themeJson.settings.typography.fontFamilies ) {
			const fontFamilies = childTheme.themeJson.settings.typography.fontFamilies;
			const fontSlugs = Object.keys( fontFamilies );
			const allFonts = await getFonts();
			themeJson.settings.typography.fontFamilies = fontSlugs.map( fontSlug => ( {
				"fontFamily": allFonts[ fontFamilies[ fontSlug ] ].fontFamily,
				"fontSlug": fontFamilies[ fontSlug ],
				"slug": fontSlug + "-font",
				"name": capitalize( fontSlug ) + " (" + allFonts[ fontFamilies[ fontSlug ] ].name + ")",
				"google": allFonts[ fontFamilies[ fontSlug ] ].google
			} ) );
		}

		if ( childTheme.themeJson.settings.typography.fontSizes ) {
			themeJson.settings.custom.fontSizes = {
				"x-small": childTheme.themeJson.settings.typography.fontSizes[0],
				"normal": childTheme.themeJson.settings.typography.fontSizes[2]
			};
			const fontSizes = [ childTheme.themeJson.settings.typography.fontSizes[1] ].concat( childTheme.themeJson.settings.typography.fontSizes.slice( 3 ) );
			themeJson.settings.typography.fontSizes = fontSizes.map( ( fontSize, index ) => ( {
				"name": fontNameConventions[ index ].name,
				"size": fontSize,
				"slug": fontNameConventions[ index ].slug,
			} ) );
		}
	}

	if ( childTheme.themeJson.styles ) {
		themeJson.styles = {};
	};

	if ( childTheme.themeJson.styles.blocks ) {
		themeJson.styles.blocks = childTheme.themeJson.styles.blocks;
	}

	if ( childTheme.themeJson.styles.elements ) {
		themeJson.styles.elements = {};
		const headingElements = [ 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' ];
		headingElements.forEach( heading => {
			themeJson.styles.elements[ heading ] = childTheme.themeJson.styles.elements.headings;
		} );
	}

	await fs.writeFile( themeDir + '/theme.json', JSON.stringify( themeJson, null, '\t' ) )
}

async function generateTemplates( childTheme ) {
	if ( ! childTheme.templates ) {
		return;
	}

	const templateDirectory = getThemeDir( childTheme.slug ) + '/block-templates/';
	const templateDirectoryExists = await fs.access( templateDirectory, constants.F_OK ).then( () => true ).catch( () => false );
	if ( ! templateDirectoryExists ) {
		await fs.mkdir( templateDirectory );
	}

	if ( childTheme.templates.index ) {
		await fs.copyFile('./templates/index/' + childTheme.templates.index + '.html', templateDirectory + 'index.html' );
	}

	if ( childTheme.templates.archive ) {
		await fs.copyFile('./templates/archive/' + childTheme.templates.archive + '.html', templateDirectory + 'archive.html' );
	}

	if ( childTheme.templates.page ) {
		await fs.copyFile('./templates/page/' + childTheme.templates.page + '.html', templateDirectory + 'page.html' );
	}

	if ( childTheme.templates.single ) {
		await fs.copyFile('./templates/single/' + childTheme.templates.single + '.html', templateDirectory + 'single.html' );
	}
}

async function generateParts( childTheme ) {
	if ( ! childTheme.parts ) {
		return;
	}

	const partsDirectory = getThemeDir( childTheme.slug ) + '/block-template-parts/';
	const partsDirectoryExists = await fs.access( partsDirectory, constants.F_OK ).then( () => true ).catch( () => false );
	if ( ! partsDirectoryExists ) {
		await fs.mkdir( partsDirectory );
	}

	if ( childTheme.parts.header ) {
		await fs.copyFile('./parts/headers/' + childTheme.parts.header + '.html', partsDirectory + 'header.html' );
	}

	if ( childTheme.parts.footer ) {
		await fs.copyFile('./parts/footers/' + childTheme.parts.footer + '.html', partsDirectory + 'footer.html' );
	}
}

async function generatePatterns( childTheme ) {
	if ( ! childTheme.patterns ) {
		return;
	}

	const themeDir = getThemeDir( childTheme.slug );
	const patternsDirectory = themeDir + '/inc/patterns/';
	const patternsDirectoryExists = await fs.access( patternsDirectory, constants.F_OK ).then( () => true ).catch( () => false );
	if ( ! patternsDirectoryExists ) {
		await fs.mkdir( themeDir + '/inc/' );
		await fs.mkdir( patternsDirectory );
	}

	fsExtra.copy( './patterns/' + childTheme.patterns, patternsDirectory );

	const dir = await fs.opendir( patternsDirectory );
	const patternNames = [];
	for await (const dirent of dir) {
		patternNames.push( dirent.name );
		const patternString = await fs.readFile( patternsDirectory + '/' + dirent.name, 'utf8' );
		const sourcesMatch = patternString.match( 'Sources:' );
		const packageMatch = patternString.match( '@package' );
		if ( sourcesMatch && sourcesMatch.index && packageMatch && packageMatch.index ) {
			const sourcesForPattern = patternString.slice( sourcesMatch.index, packageMatch.index ).replace( / /g, '' ).split( '*' );
			sources = sources.concat( sourcesForPattern );
		}
	}
	const patternNamesString = patternNames.join( ',' );
	sources = [...new Set(sources)].sort().filter( source => source !== '' && source !== '\n' );


	const blockPatternsPhp = getBlockPatternsPhp( childTheme, patternNamesString );
	await fs.writeFile( themeDir + '/inc/block-patterns.php', blockPatternsPhp );
}

async function generateAssets( childTheme ) {
	if ( ! childTheme.assets ) {
		return;
	}

	const assetsDirectory = getThemeDir( childTheme.slug ) + '/assets/';
	const assetsDirectoryExists = await fs.access( assetsDirectory, constants.F_OK ).then( () => true ).catch( () => false );
	if ( ! assetsDirectoryExists ) {
		await fs.mkdir( assetsDirectory );
	}

	fsExtra.copy( './assets/' + childTheme.assets, assetsDirectory );
}

async function generateStyleCss( childTheme ) {
	let styleCss = await getStyleCss( getThemeDir( 'blockbase' ) );
	const themeDir = getThemeDir( childTheme.slug );
	styleCss = styleCss.replace( 'Theme Name: Blockbase', 'Theme Name: ' + childTheme.name );
	styleCss = styleCss.replace( 'trunk/blockbase', 'trunk/' + childTheme.slug );
	styleCss = styleCss.replace( /Description: (.+)/, 'Description: ' + childTheme.description );
	styleCss = styleCss.replace( /Version: (.+)/, 'Version: ' + childTheme.version );
	styleCss = styleCss.replace( 'Text Domain: blockbase', 'Template: ' + childTheme.template + '\r\nText Domain: ' + childTheme.slug );
	styleCss = styleCss.replace( /Blockbase/g, childTheme.name );
	await fs.writeFile( themeDir + '/style.css', styleCss );
}

async function getScreenshot( childTheme ) {
	fsExtra.copy( './screenshots/' + childTheme.slug + '.png', getThemeDir( childTheme.slug ) + '/screenshot.png' );
}

async function generateReadmeTxt( childTheme ) {
	const readmeText = getReadmeTxt( childTheme );
	const themeDir = getThemeDir( childTheme.slug );
	await fs.writeFile( themeDir + '/readme.txt', readmeText );
}

async function generateChildren() {
	const children = await getThemes();
	children.forEach( async childTheme => {
		const childThemeDirectory = getThemeDir( childTheme.slug );
		const childThemeDirectoryExists = await fs.access( childThemeDirectory, constants.F_OK ).then( () => true ).catch( () => false );
		if ( ! childThemeDirectoryExists ) {
			await fs.mkdir( childThemeDirectory );
		}

		// Reset the sources array for each theme.
		sources = [];
		await generatePackageJson( childTheme );
		await generatePatterns( childTheme );
		await generateAssets( childTheme );
		await generateStyleCss( childTheme );
		await generateThemeJson( childTheme );
		await generateTemplates( childTheme );
		await generateParts( childTheme );
		await generateReadmeTxt( childTheme );
		await getScreenshot( childTheme );
		console.log( "\x1b[32m", "Rebuilt " + childTheme.name );
	} );
}
