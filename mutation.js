module.exports = {
  ci: {
    upload: {
      target: 'temporary-public-storage',
    },
    assert: {
      assertions: {
        mutationScore: ['error', { minScore: 0.9 }],
      },
    },
  },
};
