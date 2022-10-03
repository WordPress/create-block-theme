=== Create Block Theme ===
Author: @wordpressdotorg
Contributors: @chaosexanima, @mikachan, @onemaggie, @pbking, @scruffian
Donate link: https://automattic.com/
Tags: themes, theme, block-theme
Requires at least: 6.0
Tested up to: 6.0
Stable tag: 1.2.0
Requires PHP: 7.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A WordPress plugin to create block themes.

== Description ==

This plugin allows you to:
- Create a new theme, blank theme, child theme or style variation.
- Embed Google Fonts in your theme
- Embed local font assets in your theme

= Step 1 – Setup =
Install and activate the [Create Block Theme](https://wordpress.org/plugins/create-block-theme) plugin.

In the WordPress Admin Dashboard, under Appearance there will be three new pages called:
- Create Block Theme
- Embed Google font in your current theme
- Embed local font file assets

= Step 2 – Style Customizations =
Make changes to your site styles and templates using the Site Editor. You can also include new fonts using the plugin options.

= Step 3 – Export =
Still in the WordPress dashboard, navigate to "Appearance" -> "Create Block Theme" section. Select one of the available options and then, if necessary, add the details for the theme here. These details will be used in the style.css file. Click "Create theme” button, to save the theme.

== Changelog ==

= 1.2.0 = 
Embed Google fonts and local font files in theme (#113)
Change button text (#112)
Add check and directory creation for template and parts folders. (#110)
Change theme.json schema of blank theme if Gutenberg isn't installed. (#107)

= 1.1.3 = 
update links, screenshots of the new changes (#97)
Add $schema and use Gutenberg classes (#99)
Update readme to include latest features (#100)
Generate $schema URL in the same way as core. (#105)

= 1.1.2 =
Save a theme variation (#90)
Make UI string 'Create Block Theme' can be translatable (#92)

= 1.0.1 = 
Add option to create blank theme. (#70)
Improve form instructions (#76)
Form cleanup and Theme name check (#77) 
Get the correct merged theme.json data (#88)

= 1.0 =
* Initial version.
