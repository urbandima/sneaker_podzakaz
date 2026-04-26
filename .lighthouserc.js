'use strict';

module.exports = {
  ci: {
    collect: {
      url: [
        'http://localhost:8080/',
        'http://localhost:8080/catalog',
        'http://localhost:8080/account/login',
      ],
      numberOfRuns: 1,
      settings: {
        chromeFlags: '--no-sandbox --disable-dev-shm-usage',
        formFactor: 'desktop',
        screenEmulation: { disabled: true },
      },
    },
    assert: {
      assertions: {
        'categories:performance':     ['warn',  { minScore: 0.80 }],
        'categories:accessibility':   ['error', { minScore: 0.95 }],
        'categories:best-practices':  ['error', { minScore: 0.95 }],
        'categories:seo':             ['error', { minScore: 0.95 }],
      },
    },
    upload: {
      target: 'filesystem',
      outputDir: '.lighthouseci',
    },
  },
};
