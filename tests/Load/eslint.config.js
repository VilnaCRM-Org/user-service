import js from '@eslint/js';

export default [
  js.configs.recommended,

  {
    languageOptions: {
      ecmaVersion: 2022,
      sourceType: 'module',
      globals: {
        // Browser globals
        window: 'readonly',
        document: 'readonly',
        navigator: 'readonly',
        console: 'readonly',

        // Node.js globals
        process: 'readonly',
        Buffer: 'readonly',
        __dirname: 'readonly',
        __filename: 'readonly',
        exports: 'writable',
        module: 'writable',
        require: 'readonly',
        global: 'readonly',

        describe: 'readonly',
        it: 'readonly',
        test: 'readonly',
        expect: 'readonly',
        beforeEach: 'readonly',
        afterEach: 'readonly',
        beforeAll: 'readonly',
        afterAll: 'readonly',
        jest: 'readonly',

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
    },
  },

  {
    files: ['utils/utils.js', 'utils/**/*.js'],
    rules: {
      'no-undef': 'off',
      'no-console': 'off',
      'no-unused-vars': 'off',
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
    ignores: [
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
  },
];
