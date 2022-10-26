const semver = require('semver');
const fs = require('fs');
const core = require('@actions/core');
const simpleGit = require('simple-git');

const git = simpleGit.default();

const releaseType = process.env.RELEASE_TYPE;
const VALID_RELEASE_TYPES = ['major', 'minor', 'patch'];

async function getChangesSinceGitTag (tag) {
    const changes = await git.log(["--reverse", "--merges", `HEAD...${tag}`]);
    return changes;
}

async function updateVersion () {
    if ( !VALID_RELEASE_TYPES.includes(releaseType) ) {
        console.error("❎  Error: Release type is not valid. Valid release types are: major, minor, patch.");
        process.exit(1);
    }

    if (!fs.existsSync('./package.json')) {
        console.error("❎  Error: package.json file not found.");
        process.exit(1);
    }

    if (!fs.existsSync('./readme.txt')) {
        console.error("❎  Error: readme.txt file not found.");
        process.exit(1);
    }

    const package = require('./package.json');
    const currentVersion = package.version;
    const newVersion = semver.inc(currentVersion, releaseType);
    const currentTag = `v${currentVersion}`;
    const newTag = `v${newVersion}`;

    if (!semver.valid(currentTag)) {
        console.error(`❎  Error: current tag ( ${ currentTag } ) is not a valid semver version."`);
        process.exit(1);
    }

    // get changes since last tag
    const changes = await getChangesSinceGitTag(currentTag);

    // check if there are any changes
    if (!changes || changes.total === 0) {
        console.error("❎  No changes since last tag. There is nothing to release.");
        process.exit(1);
    }

    // update package.json version
    package.version = newVersion;
    fs.writeFileSync('./package.json', JSON.stringify(package, null, 2));
    console.info('✅ Version updated', currentTag, '=>', newTag);
    
    // update readme.txt version with the new changelog
    const readme = fs.readFileSync('./readme.txt', 'utf8');
    const changelogChanges = changes.all.map(change => `* ${ change.body || change.message }`).join('\n');
    const newChangelog = `== Changelog ==\n\n= ${ newVersion } =\n\n${ changelogChanges }`;
    let newReadme = readme.replace("== Changelog ==", newChangelog);
    // update version in readme.txt
    newReadme = newReadme.replace(/Stable tag: (.*)/, `Stable tag: ${ newVersion }`);
    fs.writeFileSync('./readme.txt', newReadme);
    console.info('✅  Readme version updated', currentTag, '=>', newTag);

    // output data to be used by the next steps of the github action
    core.setOutput('NEW_TAG', newTag);
    core.setOutput('CHANGELOG', changelogChanges);
}

updateVersion();


