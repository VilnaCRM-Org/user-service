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
  mutate: [
    './src/test/unit/**/*.ts',
    './src/test/unit/**/*.tsx',
    './src/test/testing-library/**/*.ts',
    './src/test/testing-library/**/*.tsx',
  ],
};

export default config;
