import semver from 'semver';
import packageJSON from './package.json' assert { type: 'json' };

const { engines } = packageJSON;

const version = engines.node;
if (!semver.satisfies(process.version, version)) {
  console.log(
    `Required node version ${version} not satisfied with current version ${process.version}.`,
  );
  process.exit(1);
}
