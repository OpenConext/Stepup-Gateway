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

    #[\Behat\Step\Then('/^the response should contain \\\'([^\\\']*)\\\'$/')]
    public function theResponseShouldContain($string): void
    {
        $this->assertSession()->responseContains($string);
    }

    #[\Behat\Step\Then('/^the response should match xpath \\\'([^\\\']*)\\\'$/')]
    public function theResponseShouldMatchXpath($xpath): void
    {
        $document = new DOMDocument();
        if ($this->getSession()->getDriver() instanceof ChromeDriver) {
            // Chrome uses a user friendly viewer, get the xml from the dressed document and assert on that xml.
            $this->getSession()->wait(1000, "document.getElementById('webkit-xml-viewer-source-xml') !== null");
            $xml = $this->getSession()->evaluateScript("document.getElementById('webkit-xml-viewer-source-xml').innerHTML");
        } else {
            $xml = $this->getSession()->getPage()->getContent();
        }
        $document->loadXML($xml);

        $this->assertXpath($this->buildXPath($document), $xpath, true);
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
    }
}
