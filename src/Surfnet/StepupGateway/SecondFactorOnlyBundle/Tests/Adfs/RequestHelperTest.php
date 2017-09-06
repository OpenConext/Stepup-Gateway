<?php

namespace Surfnet\StepupGateway\SecondFactorOnlyBundle\Test\Adfs;

use Mockery as m;
use Psr\Log\LoggerInterface;
use Surfnet\StepupGateway\SecondFactorOnlyBundle\Adfs\RequestHelper;
use Surfnet\StepupGateway\SecondFactorOnlyBundle\Adfs\StateHandler;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;

class RequestHelperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RequestHelper
     */
    private $helper;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var ParameterBag
     */
    private $parameterBag;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var StateHandler
     */
    private $stateHandler;

    public function setUp()
    {
        $this->logger = m::mock(LoggerInterface::class);
        $this->logger->shouldIgnoreMissing();

        $this->stateHandler = m::mock(StateHandler::class);

        $this->helper = new RequestHelper($this->stateHandler, $this->logger);
        $this->request = m::mock(Request::class);
        $this->parameterBag = m::mock(ParameterBag::class);
        $this->request->request = $this->parameterBag;
    }

    /**
     * @test
     */
    public function it_can_test_if_request_is_not_from_adfs()
    {
        $this->parameterBag->shouldReceive('has')->once()->andReturn(false);
        $this->assertFalse($this->helper->isAdfsRequest($this->request));
    }

    /**
     * @test
     */
    public function it_can_test_if_request_is_from_adfs()
    {
        $this->parameterBag->shouldReceive('has')->times(3)->andReturn(true);
        $this->assertTrue($this->helper->isAdfsRequest($this->request));
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function it_rejects_malformed_request()
    {
        $this->request->request->shouldReceive('get')->with(RequestHelper::ADFS_PARAM_AUTH_METHOD)->andReturn('');
        $this->request->request->shouldReceive('get')->with(RequestHelper::ADFS_PARAM_CONTEXT)->andReturn('context');
        $this->request->request->shouldReceive('get')->with(RequestHelper::ADFS_PARAM_AUTHNREQUEST)->andReturn('req');
        $this->helper->transformRequest($this->request);
    }

    /**
     * @test
     */
    public function it_transforms_adfs_request()
    {
        $authnRequest = <<<AUTHNREQUEST
<samlp:AuthnRequest xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol" xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" ID="my-request-id" Version="2.0" IssueInstant="2017-08-16T14:25:06Z" Destination="https://gw-dev.stepup.coin.surf.net/app_dev.php/second-factor-only/single-sign-on" AssertionConsumerServiceURL="http://localhost:8989/simplesaml/module.php/saml/sp/saml2-acs.php/sfo-sp" ProtocolBinding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST"><saml:Issuer>http://localhost:8989/simplesaml/module.php/saml/sp/metadata.php/sfo-sp</saml:Issuer><ds:Signature xmlns:ds="http://www.w3.org/2000/09/xmldsig#">
<ds:SignedInfo><ds:CanonicalizationMethod Algorithm="http://www.w3.org/2001/10/xml-exc-c14n#"/>
<ds:SignatureMethod Algorithm="http://www.w3.org/2001/04/xmldsig-more#rsa-sha256"/>
<ds:Reference URI="#_081f8ae298580b2469f65467512150d582e2d98443"><ds:Transforms><ds:Transform Algorithm="http://www.w3.org/2000/09/xmldsig#enveloped-signature"/><ds:Transform Algorithm="http://www.w3.org/2001/10/xml-exc-c14n#"/></ds:Transforms><ds:DigestMethod Algorithm="http://www.w3.org/2001/04/xmlenc#sha256"/><ds:DigestValue>3j6hSACdbzubiAWM12fpzTuRWgUWgSwxZseXDhQzGXw=</ds:DigestValue></ds:Reference></ds:SignedInfo><ds:SignatureValue>RggbPG+XX01ftI4dMY/sAbrxV009CT6GcSTwSsR3qxt5tCdO91Xl07gkoSuRQ6Dn1pJNgNN2/cmWy3bMCsRrega43QXJAe4elW42OE6FIGlvGHLLbizKH3bwhGJVM65kFGuAHo077ao9/YLobCJeoFbDicNTPVt0aWb6ZdxWlhjaAIszFKQqdBPLAnHjRQw76WpgoBM+nNKzR8MbU4H6V94/pAbfNY67iQscN+iu9B8SIRkiGGpSw8155l7MjWGwzBUZUDp7rBW8eRzVR3NDS1jkiQ1GSqkQqsdi1Iy8nRRDdNRvhYQzlgjCNdV5LpoaWGoP21+8nlRZao3jRvdo5w==</ds:SignatureValue>
<ds:KeyInfo><ds:X509Data><ds:X509Certificate>MIIDmTCCAoGgAwIBAgIJAKyUXzwGwcqhMA0GCSqGSIb3DQEBCwUAMGMxCzAJBgNVBAYTAk5MMRAwDgYDVQQKDAdFeGFtcGxlMR4wHAYDVQQDDBVTRk8gRGVtbyBTQU1MIHNpZ25pbmcxIjAgBgkqhkiG9w0BCQEWE3N1cHBvcnRAZXhhbXBsZS5vcmcwHhcNMTYxMTA2MDgxOTIzWhcNMjYxMTA0MDgxOTIzWjBjMQswCQYDVQQGEwJOTDEQMA4GA1UECgwHRXhhbXBsZTEeMBwGA1UEAwwVU0ZPIERlbW8gU0FNTCBzaWduaW5nMSIwIAYJKoZIhvcNAQkBFhNzdXBwb3J0QGV4YW1wbGUub3JnMIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA03h5x2cV6+JKLYHO2BxHhiYoRQi/vMQHW7EHRTyfAptf+1AwuDT83LF4OA82oXw1PTO9ffkb9beFHMxBkHQ7fI7Qq4jjhw9ljtB7BPdN9S+uOhNPAhFHb0hHAIngCGg82PEi9hD18lPfS8OJIK+cSOgrCp2H5N2vel1yRXm4laCc8/nssoIoAkV6wnATBE3oSyDMKpK+evUz/oltryf7iLvfnB8XdP3dDMERaOFqstKrj50SCpMpA6AsKZ674aIHuvO/dUD0v5+UVnDjGl2Pbfz0vp+KhV8sWSQ6oBE44yxpYQBiHJi+1Wq0Vi4Vf+hZjiH4fI+qp2BmV0HAOD0mbwIDAQABo1AwTjAdBgNVHQ4EFgQU2em7W0TJzKoNNV3LNoVHeJaJpG0wHwYDVR0jBBgwFoAU2em7W0TJzKoNNV3LNoVHeJaJpG0wDAYDVR0TBAUwAwEB/zANBgkqhkiG9w0BAQsFAAOCAQEALBmM0fMx8fnNabWIIHsElk6qVGpJ6+4583pYoNT/nXrf/Lx2jwYhyyHTdFONMoHbobY0e28t4sao8GqprGFynHs5ssjhOWpADAYHV2l0lcAt0YISmRbSJk7SfHGNYr4JHI+wgt4Cfwlw6BUsVdiBM0gxFPPQrLMoPmY4ZgQoV3YyJKvq6AhhxGvyl5b54wfaEDmGuANfDSz4c3xAX8KxIOTNevUToyMY3Z2uwwEqHSyp0ayjsMoPsZymKUoNzwHQrWyGd2glqukHEPZuP0ZeHLL6dc6/zVhHt+Pwbrvq2Q1aOfiWfLljYZZZ5PNxMEXsh2ZHTkvw2IA/pYVd59Gabg==</ds:X509Certificate></ds:X509Data></ds:KeyInfo></ds:Signature><saml:Subject><saml:NameID Format="urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified">urn:collab:person:example.org:student</saml:NameID></saml:Subject><samlp:NameIDPolicy Format="urn:oasis:names:tc:SAML:2.0:nameid-format:transient" AllowCreate="true"/><samlp:RequestedAuthnContext><saml:AuthnContextClassRef>http://pilot.surfconext.nl/assurance/sfo-level2</saml:AuthnContextClassRef></samlp:RequestedAuthnContext></samlp:AuthnRequest>
AUTHNREQUEST;

        $authnRequest = base64_encode($authnRequest);

        $this->request->request->shouldReceive('get')->with(RequestHelper::ADFS_PARAM_AUTH_METHOD)->andReturn('ADFS.SCSA');
        $this->request->request->shouldReceive('get')->with(RequestHelper::ADFS_PARAM_CONTEXT)->andReturn('<EncryptedData></EncryptedData>');
        $this->request->request->shouldReceive('get')->with(RequestHelper::ADFS_PARAM_AUTHNREQUEST)->andReturn($authnRequest);

        $this->request->request->shouldReceive('set')->with(RequestHelper::SAML_AUTHNREQUST_PARAM_REQUEST, $authnRequest);

        $this->request->request->shouldReceive('remove')->with(RequestHelper::ADFS_PARAM_AUTH_METHOD);
        $this->request->request->shouldReceive('remove')->with(RequestHelper::ADFS_PARAM_CONTEXT);

        $this->stateHandler->shouldReceive('setRequestId')->with('my-request-id')->andReturn($this->stateHandler);
        $this->stateHandler->shouldReceive('setAuthMethod')->with('ADFS.SCSA')->andReturn($this->stateHandler);
        $this->stateHandler->shouldReceive('setContext')->with('<EncryptedData></EncryptedData>')->andReturn($this->stateHandler);;

        $this->helper->transformRequest($this->request);

    }


    /**
     * @test
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The received AuthnRequest does not have a request id
     */
    public function it_needs_request_id_to_store_adfs_params()
    {
        $authnRequest = <<<AUTHNREQUEST
<samlp:AuthnRequest xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol" xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" Version="2.0" IssueInstant="2017-08-16T14:25:06Z" Destination="https://gw-dev.stepup.coin.surf.net/app_dev.php/second-factor-only/single-sign-on" AssertionConsumerServiceURL="http://localhost:8989/simplesaml/module.php/saml/sp/saml2-acs.php/sfo-sp" ProtocolBinding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST"><saml:Issuer>http://localhost:8989/simplesaml/module.php/saml/sp/metadata.php/sfo-sp</saml:Issuer><ds:Signature xmlns:ds="http://www.w3.org/2000/09/xmldsig#">
<ds:SignedInfo><ds:CanonicalizationMethod Algorithm="http://www.w3.org/2001/10/xml-exc-c14n#"/>
<ds:SignatureMethod Algorithm="http://www.w3.org/2001/04/xmldsig-more#rsa-sha256"/>
<ds:Reference URI="#_081f8ae298580b2469f65467512150d582e2d98443"><ds:Transforms><ds:Transform Algorithm="http://www.w3.org/2000/09/xmldsig#enveloped-signature"/><ds:Transform Algorithm="http://www.w3.org/2001/10/xml-exc-c14n#"/></ds:Transforms><ds:DigestMethod Algorithm="http://www.w3.org/2001/04/xmlenc#sha256"/><ds:DigestValue>3j6hSACdbzubiAWM12fpzTuRWgUWgSwxZseXDhQzGXw=</ds:DigestValue></ds:Reference></ds:SignedInfo><ds:SignatureValue>RggbPG+XX01ftI4dMY/sAbrxV009CT6GcSTwSsR3qxt5tCdO91Xl07gkoSuRQ6Dn1pJNgNN2/cmWy3bMCsRrega43QXJAe4elW42OE6FIGlvGHLLbizKH3bwhGJVM65kFGuAHo077ao9/YLobCJeoFbDicNTPVt0aWb6ZdxWlhjaAIszFKQqdBPLAnHjRQw76WpgoBM+nNKzR8MbU4H6V94/pAbfNY67iQscN+iu9B8SIRkiGGpSw8155l7MjWGwzBUZUDp7rBW8eRzVR3NDS1jkiQ1GSqkQqsdi1Iy8nRRDdNRvhYQzlgjCNdV5LpoaWGoP21+8nlRZao3jRvdo5w==</ds:SignatureValue>
<ds:KeyInfo><ds:X509Data><ds:X509Certificate>MIIDmTCCAoGgAwIBAgIJAKyUXzwGwcqhMA0GCSqGSIb3DQEBCwUAMGMxCzAJBgNVBAYTAk5MMRAwDgYDVQQKDAdFeGFtcGxlMR4wHAYDVQQDDBVTRk8gRGVtbyBTQU1MIHNpZ25pbmcxIjAgBgkqhkiG9w0BCQEWE3N1cHBvcnRAZXhhbXBsZS5vcmcwHhcNMTYxMTA2MDgxOTIzWhcNMjYxMTA0MDgxOTIzWjBjMQswCQYDVQQGEwJOTDEQMA4GA1UECgwHRXhhbXBsZTEeMBwGA1UEAwwVU0ZPIERlbW8gU0FNTCBzaWduaW5nMSIwIAYJKoZIhvcNAQkBFhNzdXBwb3J0QGV4YW1wbGUub3JnMIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA03h5x2cV6+JKLYHO2BxHhiYoRQi/vMQHW7EHRTyfAptf+1AwuDT83LF4OA82oXw1PTO9ffkb9beFHMxBkHQ7fI7Qq4jjhw9ljtB7BPdN9S+uOhNPAhFHb0hHAIngCGg82PEi9hD18lPfS8OJIK+cSOgrCp2H5N2vel1yRXm4laCc8/nssoIoAkV6wnATBE3oSyDMKpK+evUz/oltryf7iLvfnB8XdP3dDMERaOFqstKrj50SCpMpA6AsKZ674aIHuvO/dUD0v5+UVnDjGl2Pbfz0vp+KhV8sWSQ6oBE44yxpYQBiHJi+1Wq0Vi4Vf+hZjiH4fI+qp2BmV0HAOD0mbwIDAQABo1AwTjAdBgNVHQ4EFgQU2em7W0TJzKoNNV3LNoVHeJaJpG0wHwYDVR0jBBgwFoAU2em7W0TJzKoNNV3LNoVHeJaJpG0wDAYDVR0TBAUwAwEB/zANBgkqhkiG9w0BAQsFAAOCAQEALBmM0fMx8fnNabWIIHsElk6qVGpJ6+4583pYoNT/nXrf/Lx2jwYhyyHTdFONMoHbobY0e28t4sao8GqprGFynHs5ssjhOWpADAYHV2l0lcAt0YISmRbSJk7SfHGNYr4JHI+wgt4Cfwlw6BUsVdiBM0gxFPPQrLMoPmY4ZgQoV3YyJKvq6AhhxGvyl5b54wfaEDmGuANfDSz4c3xAX8KxIOTNevUToyMY3Z2uwwEqHSyp0ayjsMoPsZymKUoNzwHQrWyGd2glqukHEPZuP0ZeHLL6dc6/zVhHt+Pwbrvq2Q1aOfiWfLljYZZZ5PNxMEXsh2ZHTkvw2IA/pYVd59Gabg==</ds:X509Certificate></ds:X509Data></ds:KeyInfo></ds:Signature><saml:Subject><saml:NameID Format="urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified">urn:collab:person:example.org:student</saml:NameID></saml:Subject><samlp:NameIDPolicy Format="urn:oasis:names:tc:SAML:2.0:nameid-format:transient" AllowCreate="true"/><samlp:RequestedAuthnContext><saml:AuthnContextClassRef>http://pilot.surfconext.nl/assurance/sfo-level2</saml:AuthnContextClassRef></samlp:RequestedAuthnContext></samlp:AuthnRequest>
AUTHNREQUEST;

        $authnRequest = base64_encode($authnRequest);

        $this->request->request->shouldReceive('get')->with(RequestHelper::ADFS_PARAM_AUTH_METHOD)->andReturn('ADFS.SCSA');
        $this->request->request->shouldReceive('get')->with(RequestHelper::ADFS_PARAM_CONTEXT)->andReturn('<EncryptedData></EncryptedData>');
        $this->request->request->shouldReceive('get')->with(RequestHelper::ADFS_PARAM_AUTHNREQUEST)->andReturn($authnRequest);

        $this->request->request->shouldReceive('set')->with(RequestHelper::SAML_AUTHNREQUST_PARAM_REQUEST, $authnRequest);

        $this->request->request->shouldReceive('remove')->with(RequestHelper::ADFS_PARAM_AUTH_METHOD);
        $this->request->request->shouldReceive('remove')->with(RequestHelper::ADFS_PARAM_CONTEXT);

        $this->helper->transformRequest($this->request);
    }

}
