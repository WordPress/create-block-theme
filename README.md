# Create Block Theme

Welcome to Create Block Theme - a WordPress plugin to create block themes from within the Site Editor.

It works alongside features that are already available in the Site Editor to enhance the workflow for creating block themes. After being tested in this plugin, some of the features included here may be moved into the Site Editor itself.

_The plugin is for development only — it is not intended for use on production websites, but to be used as a tool to create new themes._

This plugin allows you to:

- Export your existing theme with all customizations made in the Site Editor
- Create a new theme, blank theme, child theme, or style variation from the Site Editor
-   Embed Google Fonts locally in your theme
-   Embed local font assets in your theme

Learn more about [how to use the plugin](#how-to-use-the-plugin) or [how to contribute](#how-to-contribute).

## User Support

If you have run into an issue, you should check the [Support Forums](https://wordpress.org/support/plugin/create-block-theme/) first. The forums are a great place to get help. If you have a bug to report, please submit it to this repository as [an issue](https://github.com/WordPress/create-block-theme/issues). Please search prior to creating a new bug to confirm its not a duplicate.

## Plugin Features

### Theme Creation Options

There are six options the plugin provides to create a new theme:

#### 1. Export

Export the activated theme including the user's changes.

#### 2. Create a child theme

Creates a new child theme with the currently active theme as a parent.

#### 3. Clone the active theme

Creates a new theme by cloning the activated theme. The resulting theme will have all of the assets of the activated theme combined with the user's changes.

#### 4. Overwrite theme files

Saves the user's changes to the theme files and deletes the user's changes from the site.

#### 5. Generate blank theme

Generate a boilerplate "empty" theme inside of the current site's themes directory.

#### 6. Create a style variation

Saves user's changes as a [style variation](https://developer.wordpress.org/themes/advanced-topics/theme-json/#global-styles-variations) of the currently active theme.

### Embed Google Fonts or local font files

This feature allows you to embed fonts into your active theme. You can embed Google Fonts and local font assets.

When you add a font the plugin will add to your theme:

-   The font files to your theme's file structure under this path `./assets/fonts`.
-   The font face definitions to the `theme.json` file.

You can continue using your modified theme or you can export it as a new theme containing the new fonts.

## How to Use the Plugin

### Step 1: Setup

To use the latest release of the Create Block Theme plugin on your WordPress site: install from the plugins page in wp-admin, or [download from the WordPress.org plugins repository](https://wordpress.org/plugins/create-block-theme).

In the WordPress Admin Dashboard, under Appearance there will be three new pages called:

-   Create Block Theme
-   Embed Google font in your active theme
-   Embed local font file assets

### Step 2: Fonts, styles and templates customizations

Make changes to your site styles and templates using the Site Editor. You can also include new fonts using the plugin options.

### Step 3: Save

Still in the WordPress dashboard, navigate to "Appearance" -> "Create Block Theme" section. Select one of the available options and then, if necessary, add the details for the theme here. These details will be used in the style.css file. Click the "Generate" button to save the theme.

## How to Contribute

We welcome contributions in all forms, including code, design, documentation, and triage. Please see our [Contributing Guidelines](/CONTRIBUTING.md) for more information.
