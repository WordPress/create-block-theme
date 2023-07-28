=== Create Block Theme ===
Contributors: wordpressdotorg, mikachan, onemaggie, pbking, scruffian, mmaattiiaass, jffng, madhudollu, egregor
Tags: themes, theme, block-theme
Requires at least: 6.0
Tested up to: 6.2
Stable tag: 1.13.2
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

== Frequently Asked Questions ==

= How do I get support? =

If you run into an issue, you should check the [Support forum](https://wordpress.org/support/plugin/create-block-theme/) first. The forum is a great place to get help.

= How do I report an issue? =

If you have a bug to report, please submit it to the [GitHub repository](https://github.com/WordPress/create-block-theme/issues) as an issue. Please search prior to creating a new bug to confirm its not a duplicate.

== General Troobleshooting ==

If you are having problems, please try the following:

- Make sure you have the latest version of WordPress installed.
- Make sure you have the latest version of the plugin installed.
- Deactivate all plugins and see if this resolves the problem. If this works, re-activate the plugins one by one until you find the problematic plugin(s).
- Switch the theme to the default theme to rule out any theme-related problems.
- Check the [Support forum](https://wordpress.org/support/plugin/create-block-theme/) for similar issues.

== I'm getting a corrupt zip file when I export my theme ==

- First follow the general troubleshooting steps above.
- Please make sure you `WP_DEBUG` setting in your `wp-config.php` file is set to `false` like this: `define( 'WP_DEBUG', false );`.
- If your theme includes PHP files, ensure those files do **not** use PHP closing tags `?>` at the end of the file. If they do, remove them.


== Changelog ==

= 1.13.2 =
* Update Google Fonts JSON data from API
* Set the initial version to 1.0.0 insted of 0.0.1
* Fix: react render warning
* Update Google Fonts JSON data from API
* Update Google Fonts JSON data from API

= 1.13.1 =


= 1.13.0 =
* # Conflicts:
#	admin/create-theme/theme-readme.php
#	admin/create-theme/theme-zip.php

* Merge branch 'trunk' into fix/resources-credits-on-save
* Merge branch 'trunk' into add/subfolder-to-zip
* Persist copyright info on new theme creation
* Update Google Fonts JSON data from API
* Move check for `download_url` higher up
* Avoid white spaces or other weird characters on font asset paths.
* Merge branch 'trunk' into add/subfolder-to-zip
* Adding files to zip subfolder called as theme slug
* Update Google Fonts JSON data from API

= 1.12.1 =
* Fix double replacement in replace_namespace

= 1.12.0 =
* # Conflicts:
#	src/plugin-sidebar.js

* Add image credits input
* Update theme `version` logic to use isset()
* Update Google Fonts JSON data from API

= 1.11.0 =
* Update Google Fonts JSON data from API
* Add linebreaks before hyphen lists in readme to fix plugin repository display
* Prevent additional white space in font credits in readme.txt
* Google fonts: Change onClick handlers to onChange
* Merge pull request #374 from WordPress/fix/escape-template-text
* Update required node version and update dependencies

= 1.10.0 =
* Update Google Fonts JSON data from API
* Adding troubleshooting FAQs
* Updating "Requires at least" field of generated themes
* Improve handling of font license errors
* Fix `tabIndex` prop
* Automatically add font license info for local fonts

= 1.9.0 =
* Merge branch 'trunk' into add/handle-google-font-credits
* Update Google Fonts JSON data from API
* Fix console error in `prepareThemeNameValidation` function
* Add FAQ section to readme.txt
* Automatically add font license info for Google fonts
* Removing donate link

= 1.8.2 =
* Merge branch 'trunk' into update/tested-up-wp-version-62
* Updating Tested up to: 6.2 WordPress version
* fix tag duplication in exported theme
* Fixing error checking
* Update Google Fonts JSON data from API
* Refactor react app code for general purpose
* add build directory to php exclude list
* Do not call replace_template_namespace when overwrting theme
* Merge branch 'trunk' into fix/314
* Fix error when switching to template edit mode in the post editor
* Add useRootPaddingAwareAlignments to blank theme
* Update Google Fonts JSON data from API
* Merge branch 'trunk' into fix/314
* Avoid adding Template info to style.css if it's empty
* Fix delete font family/face when name is different from family
* Add theme name validation
* Fix export theme from Site Editor
* Strip escaping characters before printing stylesheet
* Linting unlinted file

= 1.8.1 =
* Add current WordPress version to style.css and readme.txt
* Add labels around Google font family checkbox controls
* Fix theme slug, textdomain, and template for cloned, child and sibling themes.
* Replace theme slug in templates after getting media urls from them

= 1.8.0 =
* Export style variations just with the changes made by the user
* fix issue where package-lock is not updated on version bump
* Adding default value to an to avoid error when calling export_theme_data()
* Fixing image downloading not working in some cases
* Update Google Fonts JSON data from API
* Add Export (Clone) to site editor

= 1.7.1 =
* Update screenshots
* Fix manage fonts UI and backend when no settings are defined in theme.json
* Variable font weight range

= 1.7.0 =
* Manage fonts minor refactor. Move elements from PHP to react
* Allow otf font file upload
* Local fonts section implementation in React
* Fonts outline sidebar
* Update Google Fonts JSON data from API

= 1.6.3 =
* (Fix refactor regression) Remove white spaces from theme slug

= 1.6.2 =
* Refactor font-management class
* Refactor create-block-theme class
* fix manage theme font menu casing
* Add phpcs exception to avoid PHP8.0 incompatibility errors
* Fix blank theme screenshot fatal error
* Fix lint issues using auto fixer tool
* Update Google Fonts JSON data from API
* Fix CSS lint issues
* Fix PHP lint issues
* Fix JS lint issues
* Add lint validation to PR workflows
* fix package lock sync issue

= 1.6.1 =
* Add: input for theme tags
* Placeholder URL change to TT3
* Remove white spaces from theme slugs

= 1.6.0 =
* Update main readme and add supporting docs
* Fix Depreciation Warning
* Cleanup Manage Theme Fonts UI
* Bundle template images into theme assets and make their urls relative
* Cloned themes: Add original theme name to readme.txt and style.css
* Font families collapsed by default
* Fix: Unexpected action when clicking Collapse chevron
* Lint all CSS files
* Fix: composer scripts doesn't work on Windows
* Use Gutenberg Theme JSON resolver if its available
* Update Google Fonts JSON data from API
* Replacing mkdir() calls with WordPress wp_mkdir_p() function

= 1.5.1 =
* check for DISALLOW_FILE_EDIT and simplify permission check logic
* Load google fonts data from url
* Separate styles and templates reset
* Add spinner while google fonts load instead of showing a blank page
* Add: code linting scripts
* Update Google Fonts JSON data from API
* Avoid pre commit verifications on Github actions to prevent action errors caused by linting problems

= 1.5.0 =
* Fix: browser console errors
* Fix: Adding or removing fonts fails in some Windows environments
* Add placeholder screenshot to boilerplate theme
* Refactor: Add Google Fonts section from vanilla JS to React app
* Adding demo text settings

= 1.4.0 =
* Specify node and npm versions, add nvmrc file
* Add theme screenshot uploading
* Manage fonts: Demo text editable
* Update Google Fonts JSON data from API

= 1.3.10 =
* Remove font face: avoid unwanted removal of  fontfamily.
* Add missing spaces to option labels

= 1.3.9 =
* Updating Tested up to: 6.1
* I18N: Some new UI strings are not translatable
* Replace "current theme" with "active theme" (or "currently active theme")
* Improve translation process by removing trailing spaces
* Fonts: remove font files from theme assets folder if the font face/family is removed.
* Refactor to read raw theme.json data instead of using core methods
* Update Google Fonts JSON data from API

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
