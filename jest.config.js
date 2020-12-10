module.exports = {
    collectCoverage: true,
    coverageDirectory: "coverage",
    collectCoverageFrom: [
        "src/**/*.{ts,tsx,js,jsx}",
        "!**/*test.{ts,tsx,js,jsx}",
        "!build/**",
        "!node_modules/**",
        "!**/node_modules/**",
        "!.yarn-cache/**"
    ],
    moduleFileExtensions: [
        "ts",
        "tsx",
        "js",
        "jsx",
        "json"
    ],
    modulePathIgnorePatterns: [
        "\\.snap$",
        "<rootDir>/.node_modules",
        "<rootDir>/.yarn-cache",
        "<rootDir>/build",
        "<rootDir>/dist"
    ],
    transform: {
        "^.+\\.(js)$": "<rootDir>/node_modules/babel-jest",
        "\\.(ts|tsx)$": "ts-jest"
    },
    testRegex: ".*\\.test\\.(ts|tsx|js|jsx)$",
};
