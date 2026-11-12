<?php

/**
 * Copyright 2020 SURFnet B.V.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Surfnet\StepupGateway\Behat;

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Mink\Exception\ExpectationException;
use Behat\MinkExtension\Context\MinkContext as BaseMinkContext;
use DMore\ChromeDriver\ChromeDriver;
use DOMDocument;
use DOMXPath;
use RobRichards\XMLSecLibs\XMLSecurityDSig;
use RuntimeException;
use SAML2\Constants;
use SAML2\XML\mdui\Common;
use SAML2\XML\shibmd\Scope;

/**
 * Mink-enabled context.
 */
class MinkContext extends BaseMinkContext
{
    /**
     * @var array a list of window names identified by the name the tester refers to them in the step definitions.
     * @example ['My tab' => 'WindowNameGivenByBrowser', 'My other tab' => 'WindowNameGivenByBrowser']
     */
    private $windows = [];

    /**
     * How long (in milliseconds) to wait for an expected condition in the Chrome browser before
     * failing the step. Generous on purpose: after a click the browser may still have to do a
     * redirect/auto-form-sumbit, which is can be slow intermittently slow a cold or busy machine.
     */
    private const CHROME_WAIT_TIMEOUT_MS = 5000;

    /**
     * How many characters from the start of the current page to include in failure messages. Large enough to show
     * the relevant part of the page and not just the html boilerplate.
     */
    private const FAILURE_PAGE_CONTENT_MAX_CHARS = 1500;

    /**
     * Fail early with a clear message when the Chrome DevTools API is not reachable, instead of
     * letting the mink chrome driver fail with a "Could not fetch version information" error.
     * Only applies to suites that drive a real browser (default session == "chrome").
     */
    #[\Behat\Hook\BeforeScenario]
    public function assertChromeDevToolsApiIsReachable(BeforeScenarioScope $scope): void
    {
        if ($this->getMink()->getDefaultSessionName() !== 'chrome') {
            return;  // Not using a real Chrome browser
        }

        // Assume we are using a real Chrome browser (running in a container)
        // Check if the Chrome DevTools API is reachable before proceeding and provide useful error feedback when it's
        // not available

        $apiUrl = $this->getChromeApiUrl();
        if ($apiUrl === null) {
            throw new RuntimeException(
                'The "chrome" mink session is used, but no api_url could be determined for it'
            );
        }

        $curl = curl_init($apiUrl . '/json/version');
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 5);
        $body = curl_exec($curl);
        $errno = curl_errno($curl);
        $error = curl_error($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($errno !== 0) {
            throw new RuntimeException(sprintf(
                'Cannot connect to the Chrome DevTools API at "%s" (curl error %d: %s).' . PHP_EOL
                . 'Is the chrome container running and its CDP port reachable? Try: docker ps, docker logs stepup-chrome-1',
                $apiUrl,
                $errno,
                $error
            ));
        }

        if ($status !== 200) {
            throw new RuntimeException(sprintf(
                'The Chrome DevTools API at "%s" answered HTTP %d instead of 200. Response body: %s',
                $apiUrl,
                $status,
                substr((string) $body, 0, 300)
            ));
        }

        $version = json_decode((string) $body, true);
        if (!is_array($version) || !isset($version['Browser'])) {
            throw new RuntimeException(sprintf(
                'Unexpected response from the Chrome DevTools API at "%s": %s',
                $apiUrl,
                substr((string) $body, 0, 300)
            ));
        }
    }

    /**
     * Get the CDP (Chrome DevTools Protocol) HTTP API endpoint of the Chrome container.
     * (The value of sessions.chrome.chrome.api_url in ci/qa/behat.yml)
     */
    private function getChromeApiUrl(): ?string
    {
        $driver = $this->getSession()->getDriver();
        if (!$driver instanceof ChromeDriver) {
            return null;
        }

        $property = new \ReflectionProperty(ChromeDriver::class, 'api_url');

        return $property->getValue($driver);
    }

    #[\Behat\Step\Then('/^the response should contain \\\'([^\\\']*)\\\'$/')]
    public function theResponseShouldContain($string): void
    {
        $this->assertSession()->responseContains($string);
    }

    /**
     * The gateway renders a generic error page (HTTP 500, "Error - Oops!") e.g. when the second
     * factor response of an authentication that is no longer the most recent authentication in
     * the browser session is submitted.
     */
    #[\Behat\Step\Then('I should see an error page')]
    public function iShouldSeeAnErrorPage(): void
    {
        $this->waitForPageText('Something went wrong. Please try again.');
    }

    #[\Behat\Step\Then('/^the response should match xpath \\\'([^\\\']*)\\\'$/')]
    public function theResponseShouldMatchXpath($xpath): void
    {
        $document = new DOMDocument();
        if ($this->getSession()->getDriver() instanceof ChromeDriver) {
            // Chrome uses a user friendly viewer, get the xml from the dressed document and assert on that xml.
            $xml = $this->getXmlFromChromeXmlViewer();
        } else {
            $xml = $this->getSession()->getPage()->getContent();
        }
        $document->loadXML($xml);

        $this->assertXpath($this->buildXPath($document), $xpath, true);
    }

    /**
     * Wait for CHROME_WAIT_TIMEOUT_MS for an HTTP Response with XML (e.g. a SAML Response)
     *
     * Chrome renders a raw XML HTTP response with its built-in XML viewer, which exposes the source document in
     * #webkit-xml-viewer-source-xml.
     *
     * Wait for this viewer to appear. If it does not appear within CHROME_WAIT_TIMEOUT_MS, it is assumed that the
     * current page is not the expected XML response and en error is thrown.
     * This can happen for example when an error page was rendered or if a simulated click failed.
     *
     * Throws an exception if the viewer does not appear within CHROME_WAIT_TIMEOUT_MS.
     * Returns the RAW XML content
     */
    private function getXmlFromChromeXmlViewer(): string
    {
        $session = $this->getSession();

        $appeared = $session->wait(
            self::CHROME_WAIT_TIMEOUT_MS,
            "document.getElementById('webkit-xml-viewer-source-xml') !== null"
        );
        if (!$appeared) {
            throw $this->waitTimeoutException(
                'the Chrome XML viewer element #webkit-xml-viewer-source-xml to appear, '
                . 'so the current page is not a raw XML response'
            );
        }

        // Guard against the element disappearing between the wait and this evaluation (e.g. a navigation
        // was still in flight), which would otherwise surface as "Cannot read properties of null".
        $xml = $session->evaluateScript(
            "var el = document.getElementById('webkit-xml-viewer-source-xml');"
            . " el === null ? null : el.innerHTML"
        );
        if (!is_string($xml) || $xml === '') {
            throw new ExpectationException(
                sprintf(
                    'The Chrome XML viewer element #webkit-xml-viewer-source-xml was present but empty. '
                    . 'Current URL: %s',
                    $session->getCurrentUrl()
                ),
                $session
            );
        }

        return $xml;
    }

    /**
     * Assert that the current page contains the given text.
     * Waits CHROME_WAIT_TIMEOUT_MS for the text to appear first when the browser is real (Chrome).
     * With other drivers (e.g. SymfonyDriver, which follows redirects in-kernel) the page is already settled when the
     * step runs, so there is nothing to wait for.
     *
     * Throws an exception if the text does not appear (within CHROME_WAIT_TIMEOUT_MS).
     */
    public function waitForPageText(string $text): void
    {
        $session = $this->getSession();

        if ($session->getDriver() instanceof ChromeDriver) {
            $found = $session->wait(
                self::CHROME_WAIT_TIMEOUT_MS,
                sprintf(
                    'document.body !== null && document.body.innerText.includes(%s)',
                    json_encode($text)
                )
            );
            if (!$found) {
                throw $this->waitTimeoutException(sprintf('the text %s to appear on the page', json_encode($text)));
            }
        }

        $this->assertSession()->pageTextContains($text);
    }

    /**
     * Press a button and, when driving a real browser (Chrome), wait for the navigation triggered
     * by the click to finish loading.
     */
    public function pressButtonAndWaitForNavigation(string $button): void
    {
        $session = $this->getSession();

        if (!$session->getDriver() instanceof ChromeDriver) {
            // The Symfony driver follows redirects in-kernel, there is no browser navigation to wait for.
            $this->pressButton($button);
            return;
        }

        // Handle ChromeDriver

        $buttonXpath = $session->getPage()->findButton($button)->getXpath();

        // Mark the current document; the marker is gone once a new document has loaded, so waiting
        // for it to disappear waits for the post-click navigation. This also works when the
        // response is served on the same URL as the form (e.g. a POST to /verify-second-factor/...).
        $session->executeScript('window.__behatNavigationMarker = true;');
        $session->executeScript(sprintf(
            'var el = document.evaluate(%s, document, null, XPathResult.FIRST_ORDERED_NODE_TYPE, null)'
            . '.singleNodeValue;'
            . ' if (el === null) { throw new Error("Button not found"); }'
            . ' el.click();',
            json_encode($buttonXpath)
        ));

        // Wait for the post-click navigation to settle so the request triggered by the click has
        // been sent (and processed) before the next step runs.
        $settled = $session->wait(
            self::CHROME_WAIT_TIMEOUT_MS,
            'window.__behatNavigationMarker === undefined && document.readyState === "complete"'
        );
        if (!$settled) {
            throw new \RuntimeException(
                sprintf(
                    'Navigation did not settle within the timeout (%s ms)',
                    self::CHROME_WAIT_TIMEOUT_MS)
            );
        }
    }

    /**
     * Fill the given fields (field name/label => value) and submit their form by pressing the given
     * button, using JavaScript for both (when driving Chrome), then wait for the resulting
     * navigation to settle.
     *
     * The driver's fillField() types the value key by key via CDP key events, unfortunately these do not arrive reliably.
     * Setting the values and submitting them via JavaScript (form.requestSubmit) works, and also runs faster.
     *
     * @param array<string, string> $fieldValues
     */
    public function fillFieldsAndSubmitForm(array $fieldValues, string $button): void
    {
        $session = $this->getSession();

        if (!$session->getDriver() instanceof ChromeDriver) {
            // The Symfony driver follows redirects in-kernel, there is no browser navigation to wait for.
            foreach ($fieldValues as $field => $value) {
                $this->fillField($field, $value);
            }
            $this->pressButton($button);
            return;
        }

        $fieldXpaths = [];
        $values = [];
        foreach ($fieldValues as $field => $value) {
            $fieldXpaths[] = $session->getPage()->findField($field)->getXpath();
            $values[] = $value;
        }
        $buttonXpath = $session->getPage()->findButton($button)->getXpath();

        $session->executeScript(sprintf(
            'window.__behatNavigationMarker = true;'
            . 'var b = document.evaluate(%s, document, null, XPathResult.FIRST_ORDERED_NODE_TYPE, null).singleNodeValue;'
            . 'if (b === null) { throw new Error("Button not found"); }'
            . 'var form = b.form;'
            . 'if (form === null) { throw new Error("Button does not belong to a form"); }'
            . 'var fieldXpaths = %s;'
            . 'var values = %s;'
            . 'for (var i = 0; i < fieldXpaths.length; i++) {'
            . '  var f = document.evaluate(fieldXpaths[i], document, null, XPathResult.FIRST_ORDERED_NODE_TYPE, null).singleNodeValue;'
            . '  if (f === null) { throw new Error("Field not found"); }'
            . '  f.value = values[i];'
            . '}'
            . 'form.requestSubmit(b);',
            json_encode($buttonXpath),
            json_encode($fieldXpaths),
            json_encode($values)
        ));

        $session->wait(
            self::CHROME_WAIT_TIMEOUT_MS,
            'window.__behatNavigationMarker === undefined && document.readyState === "complete"'
        );
    }

    /**
     * Build the exception thrown when a wait for a Chrome page condition times out. Includes the
     * current URL and the start of the page content, so a failure shows what page was actually
     * rendered instead of the expected one.
     */
    private function waitTimeoutException(string $waitingFor): ExpectationException
    {
        $session = $this->getSession();

        return new ExpectationException(
            sprintf(
                'Timed out after %d milliseconds waiting for %s. Current URL: %s. Page content (first %d chars): %s',
                self::CHROME_WAIT_TIMEOUT_MS,
                $waitingFor,
                $session->getCurrentUrl(),
                self::FAILURE_PAGE_CONTENT_MAX_CHARS,
                substr($session->getPage()->getContent(), 0, self::FAILURE_PAGE_CONTENT_MAX_CHARS)
            ),
            $session
        );
    }

    #[\Behat\Step\Then('/^the ADFS response should match xpath \\\'([^\\\']*)\\\'$/')]
    public function theAdfsResponseShouldMatchXpath($xpath): void
    {
        $document = new DOMDocument();
        $xml = $this->getSession()->getPage()->findById('saml-response-xml')->getText();
        $document->loadXML($xml);

        $this->assertXpath($this->buildXPath($document), $xpath, true);
    }

    #[\Behat\Step\Then('/^the ADFS response should carry the ADFS POST parameters$/')]
    public function theAdfsResponseShouldHaveAdfsPostParams(): void
    {
        $context = $this->getSession()->getPage()->findById('Context')->getText();
        $authMethod = $this->getSession()->getPage()->findById('AuthMethod')->getText();
        if ($context !== '<EncryptedData></EncryptedData>') {
            throw new ExpectationException('The Adfs Context POST parameter was not found or contained an invalid value', $this->getSession());
        }
        if ($authMethod !== 'ADFS.SCSA') {
            throw new ExpectationException('The Adfs AuthMethod POST parameter was not found or contained an invalid value', $this->getSession());
        }
    }

    #[\Behat\Step\Then('/^the response should not match xpath \\\'([^\\\']*)\\\'$/')]
    public function theResponseShouldNotMatchXpath($xpath): void
    {
        $document = new DOMDocument();
        $document->loadXML($this->getSession()->getPage()->getContent());

        $this->assertXpath($this->buildXPath($document), $xpath, false);
    }

    #[\Behat\Step\Then('/^the AuthnRequest sent to the GSSP should be addressed to "([^"]*)"$/')]
    public function theAuthnRequestSentToTheGsspShouldBeAddressedTo(string $url): void
    {
        $actualUrl = $this->getGsspAuthnRequestUrl();
        $actualUrlWithoutQuery = strtok($actualUrl, '?');

        if ($actualUrlWithoutQuery !== $url) {
            throw new ExpectationException(
                sprintf(
                    'Expected the AuthnRequest sent to the GSSP to be addressed to "%s", but it was addressed to "%s"',
                    $url,
                    $actualUrlWithoutQuery
                ),
                $this->getSession()
            );
        }
    }

    #[\Behat\Step\Then('/^the AuthnRequest sent to the GSSP should match xpath \\\'([^\\\']*)\\\'$/')]
    public function theAuthnRequestSentToTheGsspShouldMatchXpath($xpath): void
    {
        $this->assertXpath($this->buildXPath($this->getGsspAuthnRequestDocument()), $xpath, true);
    }

    #[\Behat\Step\Then('/^the AuthnRequest sent to the GSSP should not match xpath \\\'([^\\\']*)\\\'$/')]
    public function theAuthnRequestSentToTheGsspShouldNotMatchXpath($xpath): void
    {
        $this->assertXpath($this->buildXPath($this->getGsspAuthnRequestDocument()), $xpath, false);
    }

    // SymfonyDriver's client follows redirects in-kernel and has no DNS, so it "visits" the
    // unresolvable GSSP host anyway (404s on Gateway's own router) - the Location header or the
    // resulting current URL still carry the full external URL, SAMLRequest param included. This
    // is the only place mdui:UIInfo is ever observable: it's never in the final SP-facing Response.
    private function getGsspAuthnRequestUrl(): string
    {
        $candidates = [];

        $locationHeader = $this->getSession()->getResponseHeader('Location');
        if ($locationHeader) {
            $candidates[] = $locationHeader;
        }

        $currentUrl = $this->getSession()->getCurrentUrl();
        if ($currentUrl) {
            $candidates[] = $currentUrl;
        }

        foreach ($candidates as $candidate) {
            if (str_contains($candidate, 'SAMLRequest=')
                && !str_contains($candidate, 'gateway.dev.openconext.local')
            ) {
                return $candidate;
            }
        }

        throw new ExpectationException(
            'Could not find a redirect towards a GSSP carrying a SAMLRequest. ' .
            'Candidates seen: ' . implode(', ', $candidates),
            $this->getSession()
        );
    }

    private function getGsspAuthnRequestDocument(): DOMDocument
    {
        $url = $this->getGsspAuthnRequestUrl();
        $query = (string) parse_url($url, PHP_URL_QUERY);
        parse_str($query, $params);

        if (!isset($params['SAMLRequest'])) {
            throw new ExpectationException(
                sprintf('The URL "%s" did not carry a SAMLRequest parameter', $url),
                $this->getSession()
            );
        }

        $xml = gzinflate(base64_decode($params['SAMLRequest']));
        if ($xml === false) {
            throw new ExpectationException(
                'Unable to decode the SAMLRequest parameter into XML',
                $this->getSession()
            );
        }

        $document = new DOMDocument();
        $document->loadXML($xml);

        return $document;
    }

    private function buildXPath(DOMDocument $document): DOMXPath
    {
        $xpathObj = new DOMXPath($document);
        $xpathObj->registerNamespace('ds', XMLSecurityDSig::XMLDSIGNS);
        $xpathObj->registerNamespace('mdui', Common::NS);
        $xpathObj->registerNamespace('mdash', Common::NS);
        $xpathObj->registerNamespace('shibmd', Scope::NS);
        $xpathObj->registerNamespace('samlp', Constants::NS_SAMLP);
        $xpathObj->registerNamespace('saml', Constants::NS_SAML);
        $xpathObj->registerNamespace('gssp', 'urn:mace:surf.nl:stepup:gssp-extensions');

        return $xpathObj;
    }

    private function assertXpath(DOMXPath $xpathObj, string $xpath, bool $shouldMatch): void
    {
        $nodeList = $xpathObj->query($xpath);

        if ($shouldMatch) {
            if (!$nodeList || $nodeList->length === 0) {
                $message = sprintf('The xpath "%s" did not result in at least one match.', $xpath);
                throw new ExpectationException($message, $this->getSession());
            }
            return;
        }

        if ($nodeList && $nodeList->length > 0) {
            $message = sprintf(
                'The xpath "%s" resulted in "%d" matches, where it should result in no matches"',
                $xpath,
                $nodeList->length
            );
            throw new ExpectationException($message, $this->getSession());
        }
    }

    #[\Behat\Step\Given('/^I should see URL "([^"]*)"$/')]
    public function iShouldSeeUrl($url): void
    {
        $this->assertSession()->responseContains($url);
    }

    #[\Behat\Step\Given('/^I should not see URL "([^"]*)"$/')]
    public function iShouldNotSeeUrl($url): void
    {
        $this->assertSession()->responseNotContains($url);
    }

    #[\Behat\Step\Given('/^I open (\d+) browser tabs identified by "([^"]*)"$/')]
    public function iOpenTwoBrowserTabsIdentifiedBy($numberOfTabs, $tabNames): void
    {
        // On successive scenarios, reset the session to get rid of browser (session) state from previous scenarios
        if ($this->getMink()->getSession()->isStarted()) {
            $this->getMink()->getSession()->restart();
        }
        // Make sure the browser is ready (without this other browser interactions fail)
        $this->getSession()->visit($this->locatePath('#'));

        $tabs = explode(',', $tabNames);
        if (count($tabs) != $numberOfTabs) {
            throw new RuntimeException(
                'Please identify all tabs you are opening in order to refer to them at a later stage'
            );
        }

        foreach ($tabs as $tab) {
            $tab = trim($tab);
            $windowsBeforeOpen = $this->getSession()->getWindowNames();

            $this->getMink()->getSession()->executeScript("window.open('/','_blank');");

            $newWindowName = $this->waitForNewlyOpenedWindow($windowsBeforeOpen);

            if ($newWindowName === null) {
                throw new RuntimeException(
                    sprintf('Failed to detect newly opened browser tab for "%s"', $tab)
                );
            }

            $this->windows[$tab] = $newWindowName;
        }
    }

    private function waitForNewlyOpenedWindow(array $windowsBeforeOpen): ?string
    {
        $maxAttempts = 10;
        $sleepMicroseconds = 100000;

        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            usleep($sleepMicroseconds);

            $currentWindows = $this->getSession()->getWindowNames();
            $newWindowName = $this->findNewlyOpenedWindow($windowsBeforeOpen, $currentWindows);

            if ($newWindowName !== null) {
                return $newWindowName;
            }
        }

        return null;
    }

    private function findNewlyOpenedWindow(array $before, array $after): ?string
    {
        $newWindows = array_values(array_diff($after, $before));

        if (count($newWindows) === 1) {
            return $newWindows[0];
        }

        return null;
    }

    #[\Behat\Step\Given('/^I switch to "([^"]*)"$/')]
    public function iSwitchToWindow($windowName): void
    {
        // (re) set the default session to the chrome session.
        $this->switchToWindow($windowName);
    }

    public function switchToWindow($windowName): void
    {
        if (!isset($this->windows[$windowName])) {
            throw new RuntimeException(sprintf('Unknown window/tab name "%s"', $windowName));
        }
        $this->getSession()->switchToWindow($this->windows[$windowName]);

        // The mink chrome driver only re-points its websocket connection, it does not activate the tab in the browser.
        // Activate the tab like a user switching tabs would, so that our simulated input (clicks, key presses) is
        // processed in the selected tab.
        if ($this->getSession()->getDriver() instanceof ChromeDriver) {
            $this->activateChromeTab($this->windows[$windowName]);
        }
    }

    private function activateChromeTab(string $targetId): void
    {
        $apiUrl = $this->getChromeApiUrl();
        if ($apiUrl === null) {
            throw new RuntimeException('No api_url could be determined for the "chrome" mink session');
        }

        $curl = curl_init($apiUrl . '/json/activate/' . $targetId);
        // Chrome >= 111 only accepts PUT for the mutating /json/* endpoints.
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 5);
        curl_exec($curl);

        if (curl_errno($curl)) {
            $error = curl_error($curl);
            curl_close($curl);
            throw new RuntimeException(sprintf('Failed to activate browser tab "%s": %s', $targetId, $error));
        }
        curl_close($curl);
    }
}
