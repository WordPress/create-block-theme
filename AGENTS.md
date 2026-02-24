# AGENTS.md

This file provides guidance to AI coding agents working in this repository.

## Repository Overview

This is a WordPress plugin that allows you to create block themes from within the WordPress Editor. The main purpose of the plugin is to provide additional functionality on top of the existing theme features in the Editor. See README.md for more details.

The main features include:

- Export the activated theme with all the user's changes made in the Editor.
- Create a new theme, blank theme, child theme, or style variation from the Editor.
- Option to add all images used in templates to the theme's `assets` folder.
- Option to ensure the block markup used in templates and patterns is export-ready.
- Option to make most strings used in templates and patterns translate-ready.

## Tech Stack

- WordPress
- PHP
- JavaScript
- HTML
- CSS

See CONTRIBUTING.md for more details on the tech stack and development setup.

## Directory Structure

- `src/`: Source code for the plugin, including the main JavaScript files and utilities.
    - `plugin-sidebar.js`: Entry point for the Block Editor plugin sidebar (loads in the Site Editor only).
    - `admin-landing-page.js`: Entry point for the React app under Appearance > Create Block Theme.
- `assets/`: Assets for the plugin, e.g. screenshots for documentation.
- `includes/`: Includes for the plugin. This is where the main plugin code is located.
    - `includes/create-theme/`: All main PHP logic, organised as `CBT_`-prefixed static utility classes (e.g. `CBT_Theme_JSON`, `CBT_Theme_Templates`, `CBT_Theme_Fonts`).
- `src/test/`: JavaScript unit tests.
- `test/`: JavaScript Jest configuration.
- `tests/`: PHP unit tests for the plugin.
- `vendor/`: Vendor files for the plugin, including PHP dependencies.

## Commands

```bash
# Install Node dependencies
npm install

# Install Composer dependencies
composer install

# Build the plugin
npm run build

# Watch for changes and rebuild the plugin
npm run start

# Run the PHP unit tests (requires Docker; test:php:setup must succeed first)
npm run test:php:setup
npm run test:php

# Run the JavaScript unit tests
npm run test:unit

# Run the linters
npm run lint:php
npm run lint:js
npm run lint:css
npm run lint:md-docs

# Format code
npm run format
```

Before committing, run all linters and format. These are all enforced in CI.

## Conventions to Follow

- Use the WordPress coding standards.
- Use the WordPress block editor coding standards.

## Common Pitfalls

- Always keep in mind that anything in this plugin could be migrated to the WordPress Editor (Gutenberg).
- This plugin can be run on sites with or without the Gutenberg plugin installed.
- The plugin UI is gated using `wp_is_block_theme()`, which means nothing will appear on non-block themes.
- PHP code that touches theme.json must check for the `IS_GUTENBERG_PLUGIN` constant and use `WP_Theme_JSON_Gutenberg` when available, falling back to core `WP_Theme_JSON`.

## PR instructions

- Ensure build passes.
- Fix all formatting and linting issues; these are enforced through CI in PRs.

## Documentation and Links

- README.md for the plugin README.
- CONTRIBUTING.md for the plugin contributing guidelines.
- [Plugin Documentation](https://wordpress.org/plugins/create-block-theme/)
- [Plugin Repository](https://github.com/WordPress/create-block-theme)
- [Plugin Support](https://wordpress.org/support/plugin/create-block-theme/)
