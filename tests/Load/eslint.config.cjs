const { FlatCompat } = require('@eslint/eslintrc');
const js = require('@eslint/js');

const compat = new FlatCompat({
  baseDirectory: __dirname,
  recommendedConfig: js.configs.recommended,
  allConfig: js.configs.all,
});

module.exports = [
  {
    ignores: ['node_modules/**'],
  },
  ...compat.config({
    env: {
      node: true,
      es6: true,
      jest: true,
      browser: true,
    },
    parserOptions: { ecmaVersion: 2022, sourceType: 'module' },
    extends: [
      'eslint:recommended',
      'plugin:import/errors',
      'plugin:import/warnings',
      'plugin:jest-dom/recommended',
      'plugin:eslint-comments/recommended',
    ],
    rules: {
      'eslint-comments/no-use': ['error', { allow: [] }],
      'no-await-in-loop': 'warn',
      'no-restricted-syntax': 'warn',
      'no-alert': 'error',
      'no-console': 'off',
      'no-unused-vars': 'off',
      'import/prefer-default-export': 'warn',
      'max-len': 'off',
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
      'import/order': 'off',
      'import/default': 'off',
      'import/no-named-as-default-member': 'off',
      'import/no-named-as-default': 'off',
      'import/no-extraneous-dependencies': 'off',
      'import/no-unresolved': 'off',
      'import/extensions': 'off',
    },
    overrides: [
      {
        files: ['utils/utils.js'],
        rules: {
          'no-undef': 'off',
        },
      },
    ],
  }),
];
