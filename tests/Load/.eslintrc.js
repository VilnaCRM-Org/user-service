module.exports = {
  env: {
    es2022: true,
    node: true,
    browser: true,
    jest: true,
  },
  extends: ['eslint:recommended', 'airbnb-base', 'prettier'],
  parserOptions: {
    ecmaVersion: 2022,
    sourceType: 'module',
  },
  globals: {
    // K6 globals
    __ENV: 'readonly',
    __VU: 'readonly',
    __ITER: 'readonly',
    open: 'readonly',
    file: 'readonly',
    http: 'readonly',
    exec: 'readonly',
    check: 'readonly',
    group: 'readonly',
    sleep: 'readonly',
    Trend: 'readonly',
    Rate: 'readonly',
    Counter: 'readonly',
    Gauge: 'readonly',
  },
  rules: {
    'no-await-in-loop': 'warn',
    'no-restricted-syntax': 'warn',
    'no-alert': 'error',
    'no-console': 'error',
    'max-len': ['error', { code: 150 }],
    'no-extra-semi': 'off',
    quotes: ['error', 'single', { avoidEscape: true, allowTemplateLiterals: true }],
    'no-multiple-empty-lines': ['error', { max: 2, maxEOF: 0 }],
    'linebreak-style': ['error', 'unix'],
    'no-undef': 'off',
    'no-unused-vars': [
      'warn',
      {
        argsIgnorePattern: '^_',
        varsIgnorePattern: '^_|^data$|^error$',
      },
    ],
    semi: ['error', 'always'],
    'comma-dangle': ['error', 'always-multiline'],
    'no-trailing-spaces': 'error',
    'eol-last': ['error', 'always'],
    indent: ['error', 2],
    // Ігнорувати K6 модулі
    'import/no-unresolved': [
      'error',
      {
        ignore: ['^k6', '^k6/.*'],
      },
    ],
    // Дозволити .js розширення (необхідно для K6)
    'import/extensions': [
      'error',
      'never',
      {
        js: 'always',
      },
    ],
    // Дозволити деякі речі для K6
    'no-restricted-globals': [
      'error',
      {
        name: 'open',
        message: 'Use import instead of global open (except in K6 context)',
      },
    ],
    'no-plusplus': ['error', { allowForLoopAfterthoughts: true }],
    'class-methods-use-this': 'off',
    'consistent-return': 'off',
    'no-shadow': 'off',
    'no-param-reassign': ['error', { props: false }],
  },
  overrides: [
    {
      files: ['utils/utils.js', 'utils/**/*.js'],
      rules: {
        'no-undef': 'off',
        'no-console': 'off',
        'no-unused-vars': 'off',
        'no-restricted-globals': 'off',
        'no-plusplus': 'off',
        'class-methods-use-this': 'off',
        'consistent-return': 'off',
        'no-shadow': 'off',
        'no-param-reassign': 'off',
      },
    },
    {
      files: ['**/*test*.js', '**/*spec*.js', '**/test-*.js', 'utils/prepareUsers.js'],
      rules: {
        'no-console': 'off',
        'max-len': ['error', { code: 200 }],
        'no-unused-vars': 'off',
      },
    },
    {
      files: ['scripts/**/*.js'],
      rules: {
        'no-plusplus': 'off',
      },
    },
  ],
  ignorePatterns: [
    'node_modules/**',
    'results/**',
    'coverage/**',
    'dist/**',
    'build/**',
    '*.min.js',
    'vendor/**',
    '.github/**',
    '**/*.yml',
    '**/*.yaml',
    '**/*.json',
  ],
};
