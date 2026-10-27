<?php
declare(strict_types=1);

namespace Surfnet\StepupGateway\Tests\Functional\Support\Saml;

/**
 * Loads pre-signed SAML fixtures from PHP files.
 * Each fixture is a PHP file that returns a string (the signed SAML message).
 *
 * Searches in order:
 * 1. Devconf captured fixtures (Fixtures/devconf/saml/)
 * 2. Generated fixtures (Fixtures/saml/)
 */
class SamlFixtureLoader
{
    private array $searchPaths;

    public function __construct(string $fixturesPath)
    {
        $this->searchPaths = [
            $fixturesPath . '/devconf/saml',  // Captured from devconf (preferred)
            $fixturesPath . '/saml',           // Generated (fallback)
        ];
    }

    /**
     * Load a pre-signed HTTP-Redirect AuthnRequest query string.
     *
     * @param string $name Fixture name (e.g., 'sfo-redirect-loa2' or 'SfoRedirectLoa2')
     */
    public function loadRedirectAuthnRequest(string $name): string
    {
        return $this->loadFixture('authn-requests/' . $name);
    }

    /**
     * Load a pre-signed HTTP-POST AuthnRequest (base64-encoded XML).
     */
    public function loadPostAuthnRequest(string $name): string
    {
        return $this->loadFixture('authn-requests/' . $name);
    }

    /**
     * Load a pre-signed HTTP-POST Response (base64-encoded XML).
     */
    public function loadPostResponse(string $name): string
    {
        return $this->loadFixture('responses/' . $name);
    }

    /**
     * Load a fixture from a PHP file.
     * Searches all configured paths in order.
     */
    private function loadFixture(string $relativePath): string
    {
        foreach ($this->searchPaths as $basePath) {
            // Try with .php extension
            $file = $basePath . '/' . $relativePath . '.php';
            if (file_exists($file)) {
                return $this->loadPhpFixture($file);
            }

            // Try with .txt extension (for raw query strings)
            $file = $basePath . '/' . $relativePath . '.txt';
            if (file_exists($file)) {
                return trim(file_get_contents($file));
            }
        }

        throw new \RuntimeException(sprintf(
            'SAML fixture not found: %s (searched in: %s)',
            $relativePath,
            implode(', ', $this->searchPaths)
        ));
    }

    /**
     * Load a PHP fixture file that returns a string.
     */
    private function loadPhpFixture(string $file): string
    {
        $result = require $file;
        if (!is_string($result)) {
            throw new \RuntimeException(sprintf(
                'SAML fixture %s must return a string, got %s',
                $file,
                gettype($result)
            ));
        }

        return $result;
    }
}
