# AGENTS.md

This is a WordPress plugin that allows you to create block themes from within the WordPress Editor. See @README.md for more details.

## Tech Stack

- WordPress
- PHP
- JavaScript
- HTML
- CSS

See @CONTRIBUTING.md for more details on the tech stack and development setup.

## Directory Structure

- `src/`: Source code for the plugin, including the main JavaScript files and utilities.
- `assets/`: Assets for the plugin, e.g. screenshots for documentation.
- `includes/`: Includes for the plugin. This is where the main plugin code is located.
- `test/`: JavaScript Jest test setup for the plugin.
- `tests/`: PHP unit tests for the plugin.
- `vendor/`: Vendor files for the plugin, including PHP dependencies.

## Commands

```bash
# Install dependencies
npm install

# Build the plugin
npm run build

# Watch for changes and rebuild the plugin
npm run start

# Run the test setup
npm run test:php:setup

# Run the PHP unit tests
npm run test:php

# Run the JavaScript unit tests
npm run test:unit

# Run the linter
npm run lint:php
npm run lint:js
npm run lint:css
```

## Conventions to Follow

- Use the WordPress coding standards.
- Use the WordPress block editor coding standards.

## Common Pitfalls

- Always keep in mind that anything in this plugin should eventually be migrated to the WordPress Editor (Gutenberg).
- This plugin can be run on sites with or without the Gutenberg plugin installed.

## PR instructions

-   Ensure build passes.
-   Fix all formatting and linting issues; these are enforced through CI in PRs.

## Documentation and Links

- @README.md for the plugin README.
- @CONTRIBUTING.md for the plugin contributing guidelines.
- [Plugin Documentation](https://wordpress.org/plugins/create-block-theme/)
- [Plugin Repository](https://github.com/WordPress/create-block-theme)
- [Plugin Support](https://wordpress.org/support/plugin/create-block-theme/)
