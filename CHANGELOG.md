# Changelog

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

