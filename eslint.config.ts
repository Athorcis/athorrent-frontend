import eslint from '@eslint/js';
import tseslint from 'typescript-eslint';

export default tseslint.config(
    eslint.configs.recommended,
    tseslint.configs.recommended,
    {
        rules: {
            "no-prototype-builtins": "off",
            "no-magic-numbers": ["warn", { "ignore": [-1, 0, 1] }],
            "prefer-const": ["error", {
                "destructuring": "all"
            }]
        },
    }
);
