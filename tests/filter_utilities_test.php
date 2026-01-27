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
use PHPUnit\Framework\Attributes\CoversClass;
use stdClass;
use testUtils\FakeConfig;

/**
 * class FilterUtilitiesTest
 *
 * @package    filter_edusharing
 * @copyright  metaVentis GmbH — http://metaventis.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(\filter_edusharing\FilterUtilities::class)]
final class filter_utilities_test extends advanced_testcase {
    /**
     * Function test_get_redirect_url_does_not_set_child_object_in_url_if_none_is_given
     *
     * @return void
     * @throws coding_exception
     * @throws moodle_exception
     * @throws EduSharingUserException
     */
    public function test_get_redirect_url_returns_returns_proper_url(): void {
        global $CFG;
        require_once('lib/dml/tests/dml_test.php');
        require_once($CFG->dirroot . '/mod/edusharing/tests/testUtils/FakeConfig.php');
        require_once($CFG->dirroot . '/mod/edusharing/eduSharingAutoloader.php');

        $this->resetAfterTest();

        // Arrange.
        $_POST['resourceId'] = 1;
        $edureturn                 = new stdClass();
        $edureturn->object_version = '0';
        $edureturn->course = 1;
        $edureturn->id = 4;
        $edureturn->usage_id = 'abc123';
        $edureturn->object_url = 'someTestUrl';
        $dbmock = $this->getMockBuilder(moodle_database_for_testing::class)
            ->onlyMethods(['get_record'])
            ->getMock();
        $dbmock->expects($this->once())
            ->method('get_record')
            ->with('edusharing', ['id' => 1], '*', MUST_EXIST)
            ->willReturn($edureturn);
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
            ->onlyMethods(['sign', 'get_ticket'])
            ->getMock();
        $captured = [];
        $servicemock->expects($this->once())
            ->method('sign')
            ->willReturnCallback(function (string $data) use (&$captured): string {
                $captured[] = $data;
                return 'dummy-signature';
            });
        $servicemock->expects($this->once())
            ->method('get_ticket')
            ->willReturn('ticket123');

        $utilsmock = $this->getMockBuilder(UtilityFunctions::class)
            ->setConstructorArgs([$fakeconfig])
            ->onlyMethods(['get_redirect_url', 'get_object_id_from_url', 'encrypt_with_repo_key', 'get_config_entry'])
            ->getMock();
        $utilsmock->expects($this->once())
            ->method('get_redirect_url')
            ->willReturn('https://www.testurl.de?param=test');
        $utilsmock->expects($this->once())
            ->method('get_config_entry')
            ->with('application_appid')
            ->willReturn('appid123');
        $utilsmock->expects($this->once())
            ->method('get_object_id_from_url')
            ->with('someTestUrl')
            ->willReturn('nodeid123');
        $utilsmock->expects($this->once())
            ->method('encrypt_with_repo_key')
            ->with('ticket123')
            ->willReturn('dummy-encrypted-ticket');

        // Act.
        $filterutils = new FilterUtilities($servicemock, $utilsmock);
        $result = $filterutils->get_redirect_url();
        // Assert.
        $this->assertCount(1, $captured);
        $this->assertStringStartsWith('appid123', $captured[0]);
        $this->assertStringEndsWith('nodeid123', $captured[0]);

        $signeddata   = $captured[0];
        $timestampstr = substr($signeddata, strlen('appid123'), strlen($signeddata) - strlen('appid123') - strlen('nodeid123'));
        $this->assertIsNumeric($timestampstr, 'Extracted timestamp should be numeric');
        $timestamp        = (int)$timestampstr;
        $currenttimestamp = round(microtime(true) * 1000);
        $timedifference   = abs($currenttimestamp - $timestamp);
        $this->assertLessThan(5000, $timedifference, 'Timestamp should be within 5 seconds of current time');

        // Parse the URL.
        $parsedurl = parse_url($result);
        $this->assertIsArray($parsedurl, 'URL should be parseable');

        // Verify URL components.
        $this->assertEquals('https', $parsedurl['scheme'], 'URL scheme should be https');
        $this->assertEquals('www.testurl.de', $parsedurl['host'], 'URL host should be www.testurl.de');

        // Parse query parameters.
        $queryparams = [];
        if (isset($parsedurl['query'])) {
            parse_str($parsedurl['query'], $queryparams);
        }

        // Verify required parameters exist.
        $this->assertArrayHasKey('ts', $queryparams, 'URL should contain ts parameter');
        $this->assertArrayHasKey('sig', $queryparams, 'URL should contain sig parameter');
        $this->assertArrayHasKey('signed', $queryparams, 'URL should contain signed parameter');
        $this->assertArrayHasKey('ticket', $queryparams, 'URL should contain ticket parameter');

        // Verify ts parameter.
        $this->assertIsNumeric($queryparams['ts'], 'ts parameter should be numeric');
        $urlts            = (int)$queryparams['ts'];
        $tstimedifference = abs($currenttimestamp - $urlts);
        $this->assertLessThan(5000, $tstimedifference, 'ts parameter should be within 5 seconds of current time');

        // Verify sig .
        $this->assertEquals('dummy-signature', $queryparams['sig'], 'sig parameter should match expected signature');

        // Verify signed parameter.
        $this->assertStringStartsWith('appid123', $queryparams['signed'], 'signed parameter should start with appid');
        $this->assertStringEndsWith('nodeid123', $queryparams['signed'], 'signed parameter should end with nodeid');

        // Verify ticket parameter exists (encrypted value).
        $this->assertNotEmpty($queryparams['ticket'], 'ticket parameter should not be empty');
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
            ->willReturn($edureturn);
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
            ->willReturn(['detailsSnippet' => 'testSnippet']);
        $filterutils = new FilterUtilities($servicemock, $utils);
        $this->assertTrue($filterutils->get_html() === 'testSnippet');
    }
}
