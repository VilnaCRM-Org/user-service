require('dotenv').config();

module.exports = {
  ci: {
    collect: {
      url: `${process.env.NEXT_PUBLIC_WEBSITE_URL}`,
      psiStrategy: 'mobile',
      settings: {
        chromeFlags: '--no-sandbox',
      },
    },
    upload: {
      target: 'filesystem',
      outputDir: 'lhci-reports-mobile',
    },
    assert: {
      assertions: {
        'categories:performance': ['error', { minScore: 0.95 }],
        'categories:accessibility': ['error', { minScore: 0.95 }],
        'categories:bestPractices': ['error', { minScore: 0.95 }],
        'categories:seo': ['error', { minScore: 0.9 }],
      },
    },
  },
};
