<?php

namespace Symbiote\Multisites\Tests;

use Symbiote\Multisites\Model\Site;

use Page;

use Symbiote\Multisites\Multisites;

use SilverStripe\Dev\FunctionalTest;
use SilverStripe\Versioned\Versioned;

/**
 *
 *
 * @author marcus
 */
class MultisiteSiteTest extends FunctionalTest
{
    protected $usesDatabase = true;

    // NOTE(jake): 2018-04-26
    //
    // Commented out as this causes a crash in Travis / CI.
    // Assumption is because SS don't really support this PHPUnit method.
    //
    /*public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        Versioned::set_stage('Stage');

        foreach (Site::get() as $s) {
            $s->dounpublish();
            $s->delete();
        }
    }*/

    /**
     * Original value of $_REQUEST
     *
     * @var array
     */
    protected $origServer = [];

    protected function setUp()
    {
        parent::setUp();
        $this->origServer = $_SERVER;
    }
    protected function tearDown()
    {
        $_SERVER = $this->origServer;
        parent::tearDown();
    }

    public function testSiteResolves()
    {
        $_SERVER['HTTP_HOST'] = 'www.test.com';
        $site = $this->getTestSite();

        Multisites::inst()->resetCurrentSite();
        Multisites::inst()->build();

        $page = $this->getTestPage();

        $s = Site::get()->toNestedArray();

        $currentId = Multisites::inst()->getCurrentSiteId();


        // should be www.test.com, because this site is the default

        $this->assertEquals('http://www.test.com/test-page/', $page->AbsoluteLink());
    }

    public function testSecondSite() {
        $_SERVER['HTTP_HOST'] = 'www.test.com';

        $otherSite = $this->getTestSite([
            'Title' => 'Testing site',
            'Host' => 'other.test.com',
            'Theme' => 'testingtheme',
            'IsDefault' => false
        ]);

        Multisites::inst()->resetCurrentSite();
        Multisites::inst()->build();

        $page = $this->getTestPage([
            'Title' => 'Second page',
        ], $otherSite);

        $this->assertEquals('http://other.test.com/second-page/', $page->AbsoluteLink());
    }

    public function testRequestSecondPage() {
        $_SERVER['HTTP_HOST'] = 'other.test.com';
        $otherSite = $this->getTestSite([
            'Title' => 'Testing site',
            'Host' => 'other.test.com',
            'Theme' => 'testingtheme',
            'IsDefault' => false
        ]);

        Multisites::inst()->resetCurrentSite();
        Multisites::inst()->build();

        $response = $this->get('second-page');

        $this->assertEquals(200, $response->getStatusCode());
    }

    protected function getTestSite($params = null)
    {
        $this->logInWithPermission('ADMIN');
        Versioned::set_stage('Stage');

        $isDefault = isset($params['IsDefault']) ? $params['IsDefault'] : true;
        unset($params['IsDefault']);

        $params = $params ? $params : [
            'Title' => 'Testing site',
            'Host' => 'my.test.com',
            'Theme' => 'testingtheme',
        ];

        $site = Site::get()->filter($params)->first();
        if (!$site) {
            $site            = Site::create($params);
            $site->write();
            $site->IsDefault = $isDefault;
            $site->write();
            $site->doPublish();
        }

        return $site;
    }

    public function getTestPage($params = null, $site = null)
    {
        $this->logInWithPermission('ADMIN');
        Versioned::set_stage('Stage');

        $params = $params ? $params : [
            'Title' => 'Test page',
        ];

        $this->logInWithPermission('ADMIN');
        Versioned::set_stage('Stage');

        $site = $site ? $site : $this->getTestSite();

        $params['ParentID'] = $site->ID;

        $page = Page::get()->filter($params)->first();

        if (!$page) {
            $page = Page::create();
            $page->update($params);
        }
        $page->write();
        $page->publish('Stage', 'Live');
        Versioned::set_stage('Live');

        return $page;
    }
}
