# SilverStripe Multisites

[![Build Status](https://travis-ci.org/symbiote/silverstripe-multisites.svg?branch=master)](https://travis-ci.org/symbiote/silverstripe-multisites)

## Overview

Allows for multiple websites to be managed through a single site tree. 

This is an alternative module to the Subsites module; it avoids any session
tracking of the 'current' website, and doesn't perform any query modification 
at runtime to change the 'site' context of queries you execute

**Compatible with SilverStripe 3.5.x**

* Please see 2.0.x for 3.2 compatibility
* Please see the 1.2.x version for 3.1 compatibility

## Requirements

* SilverStripe 3.2.*
* [MultivalueField](https://github.com/nyeholt/silverstripe-multivaluefield)

## Installation

* Add the module and the multivaluefield module
* Run `dev/build`

## Setting up sites (and additional sites)

* In the CMS go to the Pages section and click on the Website 
* Enter in the full path to the site in the 'Host' field, without the `http://` 
  lead - eg `mysitedomain.com` or `localhost/sub/folder` for a development site
* Hit save
* To add a new site, click the Pages section; you should have an 'Add site' 
  button
* Enter details about the new site, the Host field being the most important
* Make sure each site has a page that is the homepage - the page URL Segment needs to be "/home"

## Assets management

You can optionally manage each site's assets in it's own subfolder of the root assets/ directory. Add the following extensions in your mysite/config.yml file and run ?flush=1. When editing a Site in the CMS, you now have the option to select a subfolder of assets/ to contain all assets for that site. This folder will be automatically created upon a) saving the site or b) visiting a page in the cms that has an upload field.

```yml
FileField:
  extensions:
    - MultisitesFileFieldExtension

HtmlEditorField_Toolbar:
  extensions:
    - MultisitesHtmlEditorField_ToolbarExtension
```

Files uploaded through the HTMLEditor will now be uploaded into assets/yoursite/Uploads. If you have custom upload fields in the cms however, you will need to add the following configuration to them explicitly.

```php
$fields->fieldByName('Root.Main.Image')->setFolderName('images/page-images')->useMultisitesFolder();
```

The above call to useMultisitesFolder() will change the folder name from 'images/page-images' to 'currentsitesubfolder/images/page-images'

## Known issues

When linking to a page that belongs to a different site, SiteTree::Link() will return a bad link as it prepends the base URL. Currently the best way to work around this is to implement the following in your Page.php (model class). 

```php
/**
 * Overrides SiteTree->Link. Adds a check for cases where we are linking to a page on a
 * different site in this multisites instance.  
 * @return String 
 **/
public function Link($action = null) {
	if($this->SiteID && $this->SiteID == Multisites::inst()->getCurrentSiteId()) {
		return parent::Link($action);
	} else {
		return $this->RelativeLink($action);
	}
}

```

* See [GitHub](https://github.com/sheadawson/silverstripe-multisites/issues?state=open)
