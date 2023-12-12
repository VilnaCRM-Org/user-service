const semver = require('semver');
const { engines } = require('./package.json');

const version = engines.node;
if (!semver.satisfies(process.version, version)) {
  // eslint-disable-next-line no-console
  console.log(
    `Required node version ${version} not satisfied with current version ${process.version}.`
  );
  process.exit(1);
}
