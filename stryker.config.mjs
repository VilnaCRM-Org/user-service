/** @type {import('@stryker-mutator/api/core').PartialStrykerOptions} */
const config = {
  packageManager: 'pnpm',
  reporters: ['html', 'clear-text', 'progress'],
  testRunner: 'jest',
  coverageAnalysis: 'perTest',
  plugins: ['@stryker-mutator/jest-runner'],
  tsconfigFile: 'tsconfig.json',
  mutator: 'typescript',
  transpilers: ['typescript'],
  mutate: ['./src/features/landing/components/**/*.tsx'],
  jest: {
    config: {
      testScript: 'test:unit',
    },
  },
};

export default config;
