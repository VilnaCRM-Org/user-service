module.exports = {
  root: true,
  env: {
    node: true,
    es6: true,
    jest: true,
    browser: true,
  },
  parserOptions: { ecmaVersion: 'latest', sourceType: 'module' },
  ignorePatterns: [
    'node_modules/*',
    'docker-compose.yml',
    'pnpm-lock.yaml',
    '.github/*',
    'artillery/*',
  ],
  extends: [
    'eslint:recommended',
    'plugin:storybook/recommended',
    'airbnb',
    'airbnb/hooks',
    'plugin:@next/next/recommended',
    'prettier',
  ],
  overrides: [
    {
      files: ['**/*.ts', '**/*.tsx', '**/*.spec.js', '**/*.spec.jsx'],
      parser: '@typescript-eslint/parser',
      settings: {
        react: { version: 'detect' },
        'import/resolver': {
          node: {
            extensions: ['.ts', '.tsx', '.js', ',jsx'],
          },
          typescript: {},
        },
      },
      env: {
        browser: true,
        node: true,
        es6: true,
      },
      extends: [
        'eslint:recommended',
        'plugin:import/errors',
        'plugin:import/warnings',
        'plugin:import/typescript',
        'plugin:@typescript-eslint/recommended',
        'plugin:react/recommended',
        'plugin:react-hooks/recommended',
        'plugin:jsx-a11y/recommended',
        'plugin:testing-library/react',
        'plugin:jest-dom/recommended',
      ],
      rules: {
        'no-restricted-imports': [
          'error',
          {
            patterns: ['@/features/*/*'],
          },
        ],
        'linebreak-style': ['error', 'unix'],
        'react/prop-types': 'off',

        'import/order': [
          'error',
          {
            groups: ['builtin', 'external', 'internal', 'parent', 'sibling', 'index', 'object'],
            'newlines-between': 'always',
            alphabetize: { order: 'asc', caseInsensitive: true },
          },
        ],
        'import/default': 'off',
        'import/no-named-as-default-member': 'off',
        'import/no-named-as-default': 'off',

        'react/react-in-jsx-scope': 'off',
        'react/jsx-filename-extension': ['error', { extensions: ['.jsx', '.tsx'] }],

        'jsx-a11y/anchor-is-valid': 'off',

        '@typescript-eslint/no-unused-vars': ['error'],

        '@typescript-eslint/explicit-function-return-type': ['off'],
        '@typescript-eslint/explicit-module-boundary-types': ['off'],
        '@typescript-eslint/no-empty-function': ['off'],
        '@typescript-eslint/no-explicit-any': ['off'],
        '@typescript-eslint/no-var-requires': ['off'],
      },
    },
  ],
};
