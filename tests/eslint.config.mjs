import eslint from '@eslint/js';
import globals from 'globals';
import tseslint from 'typescript-eslint';

export default tseslint.config(
    {
        ignores: ['cypress/fixtures/**'],
    },
    eslint.configs.recommended,
    tseslint.configs.recommended,
    {
        files: ['**/*.ts'],
        languageOptions: {
            globals: {
                ...globals.browser,
                ...globals.mocha,
                ...globals.node,
                Cypress: 'readonly',
                cy: 'readonly',
            },
        },
        rules: {
            '@typescript-eslint/no-unused-vars': ['error', { args: 'none' }],
            'no-magic-numbers': ['warn', { ignore: [-1, 0, 1] }],
        },
    },
);
