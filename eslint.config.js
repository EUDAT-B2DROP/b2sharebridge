// eslint.config.js
import { recommended } from '@nextcloud/eslint-config'
import eslintPluginTs from '@typescript-eslint/eslint-plugin'

export default [
    ...recommended,
    {
        plugins: {
            '@typescript-eslint': eslintPluginTs,
        },
        rules: {
            'no-console': 'off',
            'no-unused-vars': [
                "error",
                {
                    "argsIgnorePattern": "^_",
                    "varsIgnorePattern": "^_",
                    "caughtErrorsIgnorePattern": "^_"
                }
            ],
            "@typescript-eslint/no-unused-vars": [
                "warn", // or "error"
                {
                    "argsIgnorePattern": "^_",
                    "varsIgnorePattern": "^_",
                    "caughtErrorsIgnorePattern": "^_"
                }
            ],
        },
    },
    {
        ignores: [
            "node_modules/*",
            "3rdparty/*",
            "**/vendor/*",
            "**/l10n/*",
            "js/*",
            "*.config.js",
            "tests/*",
            "apps-extra"
        ]
    }
]
