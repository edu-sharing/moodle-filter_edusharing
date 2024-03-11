<?php
// This file is part of Moodle - http://moodle.org/
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

declare(strict_types=1);

// Namespace does not meet PSR. Moodle likes it this way.
namespace filter_edusharing;

use advanced_testcase;
use coding_exception;
use core\moodle_database_for_testing;
use dml_exception;
use EduSharingApiClient\EduSharingAuthHelper;
use EduSharingApiClient\EduSharingHelperBase;
use EduSharingApiClient\EduSharingNodeHelper;
use EduSharingApiClient\EduSharingNodeHelperConfig;
use EduSharingApiClient\NodeDeletedException;
use EduSharingApiClient\UrlHandling;
use EduSharingApiClient\Usage;
use EduSharingApiClient\UsageDeletedException;
use JsonException;
use mod_edusharing\EduSharingService;
use mod_edusharing\EduSharingUserException;
use mod_edusharing\UtilityFunctions;
use moodle_exception;
use stdClass;
use testUtils\FakeConfig;

/**
 * class FilterUtilitiesTest
 *
 * @package    filter_edusharing
 * @copyright  metaVentis GmbH — http://metaventis.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \filter_edusharing\FilterUtilities
 */
class filter_utilities_test extends advanced_testcase {
    /**
     * Function test_get_redirect_url_does_not_set_child_object_in_url_if_none_is_given
     *
     * @return void
     * @throws coding_exception
     * @throws moodle_exception
     * @throws EduSharingUserException
     */
    public function test_get_redirect_url_returns_return_value_of_service_method(): void {
        $this->resetAfterTest();
        global $CFG;
        require_once($CFG->dirroot . '/mod/edusharing/eduSharingAutoloader.php');
        require_once('lib/dml/tests/dml_test.php');
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $basehelper  = new EduSharingHelperBase('www.url.de', 'pkey123', 'appid123');
        $nodeconfig  = new EduSharingNodeHelperConfig(new UrlHandling(true));
        $authhelper  = new EduSharingAuthHelper($basehelper);
        $nodehelper  = new EduSharingNodeHelper($basehelper, $nodeconfig);
        $eduusage    = new Usage('node123', '1.2', '1', '2', 'usage123');
        $servicemock = $this->getMockBuilder(EduSharingService::class)
            ->setConstructorArgs([$authhelper, $nodehelper])
            ->onlyMethods(['get_redirect_url'])
            ->getMock();
        $servicemock->expects($this->once())
            ->method('get_redirect_url')
            ->with($eduusage, $user->username)
            ->will($this->returnValue('www.url.de'));
        $_POST['nodeId']      = 'node123';
        $_POST['nodeVersion'] = '1.2';
        $_POST['containerId'] = 1;
        $_POST['resourceId']  = 2;
        $_POST['usageId']     = 'usage123';
        $filterutils          = new FilterUtilities($servicemock);
        $this->assertTrue($filterutils->get_redirect_url() === 'www.url.de');
    }

    /**
     * Function test_get_html_returns_proper_html_if_all_goes_well
     *
     * @return void
     *
     * @throws EduSharingUserException
     * @throws JsonException
     * @throws NodeDeletedException
     * @throws UsageDeletedException
     * @throws coding_exception
     * @throws dml_exception
     */
    public function test_get_html_returns_proper_html_if_all_goes_well(): void {
        $this->resetAfterTest();
        global $CFG;
        require_once($CFG->dirroot . '/mod/edusharing/eduSharingAutoloader.php');
        require_once($CFG->dirroot . '/mod/edusharing/tests/testUtils/FakeConfig.php');
        require_once('lib/dml/tests/dml_test.php');
        $_POST['URL']              = 'www.test.de/test?course_id=1&obj_id=obj123&width=100&height=200&resource_id=1';
        $edureturn                 = new stdClass();
        $edureturn->object_version = '0';
        $edureturn->course         = 1;
        $edureturn->id             = 4;
        $edureturn->usage_id       = 'abc123';
        $user                      = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $eduusage        = new Usage('obj123', null, '1', '4', 'abc123');
        $edurenderparams = ['width' => '100', 'height' => '200'];
        $dbmock          = $this->getMockBuilder(moodle_database_for_testing::class)
            ->onlyMethods(['get_record'])
            ->getMock();
        $dbmock->expects($this->once())
            ->method('get_record')
            ->with('edusharing', ['id' => 1], '*', MUST_EXIST)
            ->will($this->returnValue($edureturn));
        // phpcs:ignore -- GLOBALS is supposed to be all caps.
        $GLOBALS['DB'] = $dbmock;
        $basehelper    = new EduSharingHelperBase('www.url.de', 'pkey123', 'appid123');
        $nodeconfig    = new EduSharingNodeHelperConfig(new UrlHandling(true));
        $authhelper    = new EduSharingAuthHelper($basehelper);
        $nodehelper    = new EduSharingNodeHelper($basehelper, $nodeconfig);
        $fakeconfig    = new FakeConfig();
        $fakeconfig->set_entries([
            'EDU_AUTH_KEY' => 'id',
        ]);
        $utils       = new UtilityFunctions($fakeconfig);
        $servicemock = $this->getMockBuilder(EduSharingService::class)
            ->setConstructorArgs([$authhelper, $nodehelper, $utils])
            ->onlyMethods(['get_node'])
            ->getMock();
        $servicemock->expects($this->once())
            ->method('get_node')
            ->with($eduusage, $edurenderparams, $user->id)
            ->will($this->returnValue(['detailsSnippet' => 'testSnippet']));
        $filterutils = new FilterUtilities($servicemock, $utils);
        $this->assertTrue($filterutils->get_html() === 'testSnippet');
    }
}
