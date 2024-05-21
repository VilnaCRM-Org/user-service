/** @type {import('@stryker-mutator/api/core').PartialStrykerOptions} */
const config = {
  packageManager: 'pnpm',
  reporters: ['html', 'clear-text', 'progress'],
  testRunner: 'jest',
  coverageAnalysis: 'perTest',
  plugins: ['@stryker-mutator/jest-runner'],
  tsconfigFile: 'tsconfig.json',
  mutate: ['./src/features/landing/components/**/*.tsx'],
  // tempDirName: 'stryker-tmp',
  // jest: {
  //   config: {
  //     testScript: 'test:unit',
  //   },
  // },
};

export default config;
