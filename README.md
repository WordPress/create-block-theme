# Create Block Theme
A WordPress plugin to create block themes.

This plugin allows you to:
- Create a new theme, blank theme, child theme or style variation.
- Embed Google Fonts in your theme
- Embed local font assets in your theme

The plugin is development only — not intended for use on production websites, but used as a tool to create new themes.

## Create Block Theme
This feature can be used in six ways:
### 1.Export
Export the activated theme including the user changes.

### 2. Create a child theme
Creates a new child theme with the currently activated theme as a parent.

### 3. Clone the current theme
Creates a new theme by cloning the activated theme. The resulting theme will have all of the assets of the activated theme combined with the user's changes.

### 4. Overwrite theme files
Saves user's changes to the theme files and deletes the user's changes.

### 5. Generate blank theme
Generate a boilerplate "empty" theme inside of current site's themes directory.

### 6. Create a style variation
Saves user's changes as a [style variation](https://developer.wordpress.org/themes/advanced-topics/theme-json/#global-styles-variations) of the current theme.


## Embed Google Fonts or local font files

This feature allows you to embed fonts into your current theme. You can embed Google Fonts and local font assets.

When you add a font the plugin will add to your theme:
- The font files to your theme's file structure under this path `./assets/fonts`.
- The font face definitions to the `theme.json` file.

You can continue using your modified theme or you can export it as a new theme containing the new fonts.


## How to use the plugin

### Step 1 – Setup
Install and activate the [Create Block Theme](https://wordpress.org/plugins/create-block-theme) plugin.

In the WordPress Admin Dashboard, under Appearance there will be three new pages called:
- Create Block Theme
- Embed Google font in your current theme
- Embed local font file assets

### Step 2 – Fonts, styles and templates customizations
Make changes to your site styles and templates using the Site Editor. You can also include new fonts using the plugin options.

### Step 3 – Save
Still in the WordPress dashboard, navigate to "Appearance" -> "Create Block Theme" section. Select one of the available options and then, if necessary, add the details for the theme here. These details will be used in the style.css file. Click "Generate” button, to save the theme.
