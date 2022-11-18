=== Create Block Theme ===
Contributors: wordpressdotorg, mikachan, onemaggie, pbking, scruffian, mmaattiiaass, jffng, madhudollu, egregor
Donate link: https://automattic.com/
Tags: themes, theme, block-theme
Requires at least: 6.0
Tested up to: 6.0
Stable tag: 1.3.8
Requires PHP: 7.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A WordPress plugin to create block themes.

== Description ==

This plugin allows you to:
- Create a new theme, blank theme, child theme or style variation.
- List and preview font families and font faces embeded in your theme.
- Embed Google Fonts in your theme.
- Embed local font assets in your theme.

The plugin is development only — not intended for use on production websites, but used as a tool to create new themes.

= Step 1 – Setup =
Install and activate the [Create Block Theme](https://wordpress.org/plugins/create-block-theme) plugin.

In the WordPress Admin Dashboard, under Appearance there will be three new pages called:
- Create Block Theme
- Manage fonts

= Step 2 – Style Customizations =
Make changes to your site styles and templates using the Site Editor. You can also include new fonts using the plugin options.

= Step 3 – Export =
Still in the WordPress dashboard, navigate to "Appearance" -> "Create Block Theme" section. Select one of the available options and then, if necessary, add the details for the theme here. These details will be used in the style.css file. Click "Generate” button, to save the theme.

== Changelog ==

= 1.3.8 =
* Fixes the spelling of definition
* Fixing readme contributors
* Add contributor username to readme
* Update GitHub action to avoid deprecation warning
* Update Google Fonts JSON data from API
* Update Google Fonts JSON data from API
* Check permission before running functions that need file write permissions
* Allow previewing system font

= 1.3.7 =
* Moving assets files to be auto updated by the release action

= 1.3.6 =
* Auto update assets using a github action

= 1.3.5 =
* Auto release: commit updated php file with the new version

= 1.3.4 =
* auto update version of php file

= 1.3.3 =
* Automatic release improvements

= 1.3.2 =
* Automatic release improvements

= 1.3.1 =
* Update .distignore

= 1.3.0 =
* Updating google fonts data
* Force https to load Google fonts preview
* Add the ability to select/unselect all google font variants
* Merge branch 'trunk' into try/manage-fonts
* Update google fonts JSON data automatically using a repo action
* Merge branch 'trunk' into release-action
* Merge branch 'trunk' into try/manage-fonts
* Merge branch 'try/manage-fonts' into release-action
* Manage theme fonts
* Automate release: build, version bump, changelog, deploy to wp.org
* Automate release

= 1.2.3 = 
* Add translation domain (#121)
* Check for nonce index (#120)
* Validating mime type of font file on server side (#119)

= 1.2.2 = 
* Add capabilities and nonce checks (#118)

= 1.2.1 = 
* Correcting version number

= 1.2.0 = 
* Embed Google fonts and local font files in theme (#113)
* Change button text (#112)
* Add check and directory creation for template and parts folders. (#110)
* Change theme.json schema of blank theme if Gutenberg isn't installed. (#107)

= 1.1.3 = 
* update links, screenshots of the new changes (#97)
* Add $schema and use Gutenberg classes (#99)
* Update readme to include latest features (#100)
* Generate $schema URL in the same way as core. (#105)

= 1.1.2 =
* Save a theme variation (#90)
* Make UI string 'Create Block Theme' can be translatable (#92)

= 1.0.1 = 
* Add option to create blank theme. (#70)
* Improve form instructions (#76)
* Form cleanup and Theme name check (#77) 
* Get the correct merged theme.json data (#88)

= 1.0 =
* Initial version.
