module.exports = {
    env: {
        browser: true,
        es6: true,
    },
    globals: {
        SH: 'readonly',
        Yii: 'readonly',
    },
    rules: {
        'no-undef': 'error',
        'no-unused-vars': 'warn',
        'no-unreachable': 'error',
        'no-extra-semi': 'error',
        'no-unexpected-multiline': 'error',
        'eqeqeq': ['warn', 'smart'],
    },
    parserOptions: {
        ecmaVersion: 2017,
    },
};
