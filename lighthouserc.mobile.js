require('dotenv').config();

module.exports = {
  ci: {
    collect: {
      url: `${process.env.NEXT_PUBLIC_WEBSITE_URL}`,
      psiStrategy: 'mobile',
      settings: {
        chromeFlags: '--no-sandbox',
        extraHeaders: JSON.stringify({
          [`aws-cf-cd-${process.env.NEXT_PUBLIC_CONTINUOUS_DEPLOYMENT_HEADER_NAME}`]:
            process.env.NEXT_PUBLIC_CONTINUOUS_DEPLOYMENT_HEADER_VALUE,
        }),
      },
    },
    upload: {
      target: 'filesystem',
      outputDir: 'lhci-reports-mobile',
    },
    assert: {
      assertions: {
        'categories:performance': ['error', { minScore: 0.65 }],
        'categories:accessibility': ['error', { minScore: 0.9 }],
        'categories:bestPractices': ['error', { minScore: 0.9 }],
        'categories:seo': ['error', { minScore: 0.9 }],
      },
    },
  },
};
