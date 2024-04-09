# Create Block Theme

Welcome to Create Block Theme - a WordPress plugin to create block themes from within the Site Editor.

It works alongside features that are already available in the Site Editor to enhance the workflow for creating block themes. After being tested in this plugin, some of the features included here may be moved into the Site Editor itself.

*Disclaimer:* Create Block Theme enables development features and thus is a tool that should be treated like as such; you can think of it as a Development Mode for WordPress, and you should keep in mind that changes made through this plugin could change your site and/or theme permanently.

This plugin allows you to:

- Export your existing theme with all customizations made in the Site Editor
- Create a new theme, blank theme, child theme, or style variation from the Site Editor

Learn more about Create Block Theme:

- [How to use the plugin](#how-to-use-the-plugin)
- [How to contribute](#how-to-contribute)
- [User FAQs](https://wordpress.org/plugins/create-block-theme/)

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

**DEPRECATED:** Fonts can now be managed using WordPress' native Font Library. [More information](https://wordpress.org/documentation/wordpress-version/version-6-5/#add-and-manage-fonts-across-your-site).

## How to Use the Plugin

### Step 1: Setup

To use the latest release of the Create Block Theme plugin on your WordPress site: install from the plugins page in wp-admin, or [download from the WordPress.org plugins repository](https://wordpress.org/plugins/create-block-theme).

In the WordPress Admin Dashboard, under Appearance there will be a new page called:

-   Create Block Theme

### Step 2: Styles and templates customizations

Make changes to your site styles and templates using the Site Editor.

### Step 3: Save

Still in the WordPress dashboard, navigate to "Appearance" -> "Create Block Theme" section. Select one of the available options and then, if necessary, add the details for the theme here. These details will be used in the style.css file. Click the "Generate" button to save the theme.

## How to Contribute

We welcome contributions in all forms, including code, design, documentation, and triage. Please see our [Contributing Guidelines](/CONTRIBUTING.md) for more information.
