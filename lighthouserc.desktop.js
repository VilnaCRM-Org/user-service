require('dotenv').config();

module.exports = {
  ci: {
    collect: {
      url: `${process.env.NEXT_PUBLIC_WEBSITE_URL}`,
      psiStrategy: 'desktop',
      settings: {
        preset: 'desktop',
        chromeFlags: '--no-sandbox',
      },
    },
    upload: {
      target: 'filesystem',
      outputDir: 'lhci-reports-desktop',
    },
    assert: {
      assertions: {
        'categories:performance': ['error', { minScore: 0.95 }],
        'categories:accessibility': ['error', { minScore: 0.95 }],
        'categories:bestPractices': ['error', { minScore: 0.95 }],
        'categories:seo': ['error', { minScore: 0.85 }],
      },
    },
  },
};
