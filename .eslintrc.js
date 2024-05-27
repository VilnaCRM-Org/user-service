module.exports = {
  root: true,
  env: {
    node: true,
    es6: true,
    jest: true,
    browser: true,
  },
  parserOptions: { ecmaVersion: 2022, sourceType: 'module' },
  ignorePatterns: [
    'node_modules/*',
    'docker-compose.yml',
    'pnpm-lock.yaml',
    'build/*',
    'coverage/*',
    'storybook-static/*',
    'scripts/*',
  ],
  extends: [
    'eslint:recommended',
    'plugin:storybook/recommended',
    'airbnb',
    'airbnb/hooks',
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
        'plugin:jest-dom/recommended',
        'plugin:eslint-comments/recommended',
      ],
      rules: {
        'eslint-comments/no-use': ['error', { allow: [] }],
        'react/jsx-no-bind': 'warn',
        'no-await-in-loop': 'warn',
        'no-restricted-syntax': 'warn',
        'no-alert': 'error',
        'no-console': 'error',
        'import/prefer-default-export': 'warn',
        'max-len': ['error', { code: 150 }],
        'no-restricted-imports': [
          'error',
          {
            patterns: ['@/features/*/*'],
          },
        ],
        'no-extra-semi': 'off',
        'class-methods-use-this': 'off',
        quotes: ['error', 'single', { avoidEscape: true, allowTemplateLiterals: true }],
        'no-multiple-empty-lines': [2, { max: 2, maxEOF: 0 }],
        'linebreak-style': ['error', 'unix'],

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
        'import/no-extraneous-dependencies': 'off',
        'import/no-unresolved': 'off',
        'import/extensions': 'off',
        'react/prop-types': 'off',
        'react/jsx-props-no-spreading': 'warn',
        'react/react-in-jsx-scope': 'off',

        'react/jsx-filename-extension': ['error', { extensions: ['.jsx', '.tsx'] }],

        'jsx-a11y/anchor-is-valid': 'off',

        '@typescript-eslint/no-unused-vars': ['error'],
        '@typescript-eslint/semi': ['error', 'always'],
        '@typescript-eslint/member-delimiter-style': [
          'error',
          {
            overrides: {
              interface: {
                multiline: {
                  delimiter: 'semi',
                  requireLast: true,
                },
              },
            },
          },
        ],
        '@typescript-eslint/typedef': [
          'error',
          {
            variableDeclaration: true,
            variableDeclarationIgnoreFunction: false,
            arrayDestructuring: false,
            objectDestructuring: false,
            propertyDeclaration: true,
            memberVariableDeclaration: true,
          },
        ],
        '@typescript-eslint/explicit-member-accessibility': [
          'error',
          {
            accessibility: 'explicit',
            overrides: {
              constructors: 'no-public',
            },
          },
        ],
        '@typescript-eslint/member-ordering': 'error',
        '@typescript-eslint/explicit-function-return-type': 'error',
        '@typescript-eslint/explicit-module-boundary-types': ['off'],
        '@typescript-eslint/no-empty-function': ['off'],
        '@typescript-eslint/no-explicit-any': 'error',
        '@typescript-eslint/no-var-requires': ['off'],
      },
    },
  ],
};
