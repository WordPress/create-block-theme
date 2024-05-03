# Contributing Guidelines

Welcome to Create Block Theme! All are welcome here.

## How can I contribute?

We welcome contributions in all forms, including code, design, documentation, and triage.

There is a [GitHub project board](https://github.com/orgs/WordPress/projects/188/views/1) which is used to plan and track the progress of the plugin.

### Development Setup

The basic setup for development is:

-   Node/NPM Development Tools
-   WordPress Development Site
-   Code Editor

#### Prerequisites

-   [Node.js](https://nodejs.org/en/) (v16.9.1)
-   [Composer](https://getcomposer.org/) (used for linting PHP)
-   WordPress Development Site, such as [wp-env](https://github.com/WordPress/gutenberg/blob/trunk/packages/env/README.md) or [Local](https://localwp.com/)
-   We recommend using [Node Version Manager](https://github.com/nvm-sh/nvm) (nvm) to manage your Node.js versions

We recommend following the [Gutenberg code contribution guide](https://github.com/WordPress/gutenberg/blob/trunk/docs/contributors/code/getting-started-with-code-contribution.md) for more details on setting up a development environment.

#### Code Setup

[Fork](https://docs.github.com/en/get-started/quickstart/fork-a-repo) the Create Block Theme repository, [clone it to your computer](https://docs.github.com/en/repositories/creating-and-managing-repositories/cloning-a-repository) and add the WordPress repository as [upstream](https://docs.github.com/en/pull-requests/collaborating-with-pull-requests/working-with-forks/configuring-a-remote-repository-for-a-fork)

```bash
git clone https://github.com/YOUR_GITHUB_USERNAME/create-block-theme.git
cd create-block-theme
git remote add upstream https://github.com/WordPress/create-block-theme.git
```

Run the following commands to install the plugin dependencies:

```bash
npm install
composer install
```

Run `npm run build` to build the plugin.

There are several linter commands available to help ensure the plugin follows the WordPress coding standards:

-   CSS: `npm run lint:css` & `npm run lint:css:fix`
-   JS: `npm run lint:js` & `npm run lint:js:fix`
-   PHP: `npm run lint:php` & `npm run lint:php:fix`

To test a WordPress plugin, you need to have WordPress itself installed. If you already have a WordPress environment setup, use the above Create Block Theme build as a standard WordPress plugin by putting the `create-block-theme` directory in your wp-content/plugins/ directory.

### Repository Management

Members of the [Block Themers GitHub team](https://github.com/orgs/WordPress/teams/block-themers) have write access to the repository. The team is made up of contributors who have:

- Demonstrated a commitment to improving how block themes are built in the editor
- Made 2-3 meaningful contributions to the plugin (similar to the [Gutenberg team requirements](https://developer.wordpress.org/block-editor/contributors/repository-management/#teams))

If you meet this criteria and would like to be added to the Block Themers team, feel free to ask in the [#core-editor](https://make.wordpress.org/chat/) Slack channel.

If you are not a member of the team, you can still contribute by forking the repository and submitting a pull request.

## Releasing a new version of Create Block Theme

We have an automated process for the release of new versions of Create Block Theme to the public.

### 1 - Initiate the Release Process

To begin the release process, execute the [**Create new release PR**](https://github.com/WordPress/create-block-theme/actions/workflows/release-new-version.yml) workflow from the Actions tab. Choose the type of release — major, minor, or patch — from the "Run workflow" dropdown menu. This action triggers the creation of a new Release PR, such as [#592](https://github.com/WordPress/create-block-theme/pull/592/files), which includes an automated version bump and proposed changes to the Change Log.


### 2 - Update the Release PR

Keep the Release PR current by incorporating any new changes from the `trunk` that are intended for this release. Use the `git cherry-pick [commit-hash]` command to add specific commits to the Release Branch associated with the Release PR. The Release Branch is named using the format: `release/[creation-date]/[release-type]-release`, where `[creation-date]` is the date the Release PR was created, and `[release-type]` is the type selected during the workflow initiation.


### 3 - Finalize the Release

Once the release is deemed complete and ready, it must be reviewed and approved by members of the organization. Following approval, the Release PR is merged into the main branch. This action triggers the [**Deploy to Dotorg**](https://github.com/WordPress/create-block-theme/actions/workflows/deploy-to-dotorg.yml) workflow, which tags the release on both GitHub and the WordPress Plugin Directory SVN. A Release Confirmation is then triggered on WordPress.org, notifying plugin maintainers via email of the new release awaiting confirmation. Upon confirmation, the new version becomes live on the WordPress Plugin Directory.

## Guidelines

-   As with all WordPress projects, we want to ensure a welcoming environment for everyone. With that in mind, all contributors are expected to follow our [Code of Conduct](https://make.wordpress.org/handbook/community-code-of-conduct/).

-   Contributors should follow WordPress' [coding standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/) and [accessibility coding standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/accessibility/).

-   You maintain copyright over any contribution you make. By submitting a pull request you agree to release that code under the [plugin's License](/LICENSE.md).
