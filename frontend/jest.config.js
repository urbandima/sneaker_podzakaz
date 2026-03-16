module.exports = {
  testEnvironment: 'jsdom',
  testMatch: [
    '**/tests/unit/js/**/*.test.js'
  ],
  collectCoverageFrom: [
    'web/js/**/*.js',
    '!web/js/**/*.min.js',
    '!web/js/vendor/**'
  ],
  coverageDirectory: 'tests/coverage',
  coverageReporters: ['text', 'lcov', 'html'],
  setupFilesAfterEnv: ['<rootDir>/tests/unit/js/setup.js'],
  verbose: true
};
