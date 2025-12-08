# Changelog

## 5.1.2
- Update xmlseclibs to 3.1.4 to fix canonicalization bypass error (security)

## 5.1.1
- Bugfix: LoA 1.5 SFO authentications fail for institutions with default config configuration (SSO & GSSP fallback off)
  when GSSP fallback attributes are present

## 5.1.0
- Add support for GSSP fallback authentications #447

## 5.0.4
- Bump the javascript dependencies
- Use Webauthn Devconf public key
- Make cookie domain configurable
- Enable Behat Selenium test
- Bump robrichards/xmlseclibs
- Bump PHP version of test container

## 5.0.2
- Bugfix: set the correct response Twig template when rendering the ADFS response #333 
- Enable part of the behat test suite #333 Also see: https://www.pivotaltracker.com/story/show/187940465

## 5.0.1
- Bugfix: implode was called with the wrong argument sequence. Which would work in PHP<8.0 #332

## 5.0.0
- Upgrade to Symfony 6.4 with PHP 8.2

## 4.2.2
- Simplify cookie write conditions and other improvements #302
- Bugfix: removed unwarranted state change of the selected second factor #302
- Remove "Stepup Gateway" title from header #303

## 4.2.1
Refinements and bugfixes surrounding the SSO on 2FA
- Reset the SSO cookie on every real 2FA authentication #301
- Require step up auth when cookie is broken #296
- Address some remaining SSO on 2FA issues #295
- Set SameSite response header to None #298
- Refine log messages #299 #300

## 4.2.0
- Introduction of the SSO on 2FA feature in Stepup-Middleware

**More information**
- For additional details see `docs/SsoOn2Fa.md`
- And https://www.pivotaltracker.com/epic/show/5024251

## 4.1.3
- Fix ADFS error handling not invoked for errors from a GSSP #287

## 4.1.2
After fixing error response handling in 4.1.0, the possibility of handling a failing ADFS response was not
yet implemented. And that was taken care of in this release. See #285 for details.

- Add ADFS params when rendering a GSSP response #285

## 4.1.1
- Upgrade Stepup SAML bundle to 5.0.3

## 4.1.0
- Repaired a problem with the SAML error response handling when cancelling a GSSP registration #283
- Removed security checks from test-integration GHA #283

## 4.0.4
- Upgrade of the stepup SAML bundle (upgrading the SSP SAML2 lib to v4)
- Aligned framework settings with other step-up project (Thanks Thijs)
- Fixed typo in dutch translations

## 4.0.3
-  Add ADFS parameters to SAMLResponse on error #279

## 4.0.2
- Downgrade Stepp-bundle (revert of #238) as this required us to install too much untested 
  stepup-saml-bundle features.

## 4.0.1
Add support for the new identity-vetting setup for SAT second factor tokens. In addition two important
security updates where installed. For additional details check these closed PR's https://github.com/OpenConext/Stepup-Gateway/pull/277 and https://github.com/OpenConext/Stepup-Gateway/pull/276.
I installed the updates in #275.

- Identity-vetting implementation #275

## 4.0.0
- Support support for self-asserted LoA #258

## 3.4.6
- Resolve GSSP metadata generation twig environment configuration issue #244
- Install JS and PHP dependency updates (composer & yarn)

## 3.4.5
- Use internal-collabPersonId when present #241
- Upgrade the Stepup Saml Bundle #238
- Update JS dependencies #238 #239 #240

## 3.4.4
- Fix robots.txt
- Add Github Actions workflow for tag release automation #236

## 3.4.3
- Added browserlist entry in package.json to ensure IE 11 support

## 3.4.2
* Maintenance: Install http-foundation update

## 3.4.1
* Maintenance: Update of stepup-saml-bundle and stepup-bundle

## 3.4.0
* Stepup bundle was updated to mitigate SMS bypass issues

## 3.3.0
**Feature**
Provide Spryng SMS service support #221

## 3.2.4
**Bugfix**

Fix authentication logging

## 3.2.3
**Bugfix**

Show form error messages once again  #217

## 3.2.2
**Bugfix**

Add X-UA-Compatible header to fix issues with embedded browsers

## 3.2.1
**Bugfix**

Restore simultaneous SFO and SSO authentications feature. This was accidently removed in version 3.2.0
Resolved issues with naming

## 3.2.0
**New Feature**

Add support for SAML extensions

## 3.1.0
**Improvements**

Drop support for php5. From now only php 7.2 is supported
Upgrade to Symfony 4

## 3.0.2
**Improvements**

Add Github Action testing capabilities to test simultaneous SFO flows with Selenium.

## 3.0.1
**Bugfix**

Ensure SFO SAML errors are handled correctly. In version 3.0 they yielded internal server errors, as the application tried to send
the response back using the SSO authentication session context.

Fixed in PR: Pass authentication context to error handling actions #198    

## 3.0.0
**New feature**
Allow simultaneous SFO and SSO authentications. To do this the state handling was changed slightly. Some new routes 
where added to distinguish between sfo and sso authentications in Gateway. That's why a new major release was tagged.

* Adjust state handling to allow concurrent sfo and sso authentication #193

## 2.10.6
**Bugfixes**
* Use correct ResponseContext service identifier #183
* Various security related updates #189

## 2.10.5
This is a security release that will harden the application against CVE 2019-346
 * Upgrade xmlseclibs to version 3.0.4 #186

## 2.10.4
**Improvements**
* Install security upgrades

## 2.10.3
* Rebuild the Composer lockfile

## 2.10.2
**Bugfixes**
* Remove incorrect third parameter from render call #170
* Fix copy-paste mistake #173 Thanks @tvdijen
* Fix sms challenge form #175 

**Improvements**
* The previously hardcoded "server_version" config option (Doctrine DBAL) is now configurable
* Provide behat support #171
* Adjust test config to allow smoke testing #172

## 2.10.1
**Bugfix**
Remove incorrect third parameter from render call #170

## 2.10.0
**Improvements**
* Added Controller tests #167
* Symfony 3.4.15 upgrade #166 
* Behat test support #164 169
* Removed RMT

