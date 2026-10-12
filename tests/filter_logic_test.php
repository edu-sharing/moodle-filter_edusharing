<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
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
use mod_edusharing\EduSharingService;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Class filter_logic_test
 *
 * @package    filter_edusharing
 * @copyright  metaVentis GmbH — http://metaventis.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(FilterLogic::class)]
final class filter_logic_test extends advanced_testcase {
    /**
     * Function test_apply_filter_converts_object_with_quoted_title
     *
     * The editor stores a title with double quotes as &quot;. libxml would write it single quoted, so an object
     * searched for in its libxml serialization is not found in the text and silently stays a raw preview image.
     *
     * @return void
     */
    public function test_apply_filter_converts_object_with_quoted_title(): void {
        global $CFG, $DB, $edusharingfilterloaded;
        $this->resetAfterTest();
        require_once($CFG->dirroot . '/mod/edusharing/eduSharingAutoloader.php');
        $edusharingfilterloaded = true;
        $key = openssl_pkey_new(['private_key_bits' => 2048]);
        set_config('repository_public_key', openssl_pkey_get_details($key)['key'], 'edusharing');
        set_config('application_cc_gui_url', 'https://repo.example.com/edu-sharing/', 'edusharing');
        set_config('application_appid', 'app', 'edusharing');
        set_config('EDU_AUTH_KEY', 'id', 'edusharing');
        $this->setUser($this->getDataGenerator()->create_user());
        $course = $this->getDataGenerator()->create_course();

        $title = 'Go Radio - "Rolling In The Deep" (Adele Cover) Lyric Video - Punk Goes Pop 4';
        $id    = $DB->insert_record('edusharing', (object)[
            'course'         => $course->id,
            'section_id'     => 0,
            'name'           => $title,
            'introformat'    => 0,
            'object_url'     => 'ccrep://repo/node-quoted',
            'object_version' => '0',
            'usage_id'       => 'usage-1',
            'timecreated'    => time(),
            'timemodified'   => time(),
        ]);
        $query = http_build_query([
            'resourceId' => $id,
            'nodeId'     => 'node-quoted',
            'mimetype'   => 'null',
            'mediatype'  => 'link',
        ], '', '&amp;');
        $object = '<img class="edusharing_atto" alt="' . htmlspecialchars($title) . '" title="' . htmlspecialchars($title) . '"'
            . ' src="https://moodle.example.com/mod/edusharing/preview.php?' . $query . '" width="300" height="200">';
        $this->assertStringContainsString('title="Go Radio - &quot;Rolling In The Deep&quot;', $object);
        $text = '<p>before</p><div class="edusharing-placeholder" contenteditable="false" style="">' . $object
            . '</div><p>after</p>';

        $service = $this->getMockBuilder(EduSharingService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['require_edu_login', 'has_rendering_2'])
            ->getMock();
        $service->method('has_rendering_2')->willReturn(true);

        $output = (new FilterLogic(null, $service))->apply_filter($text, ['originalformat' => FORMAT_HTML]);

        $this->assertStringNotContainsString($object, $output);
        $this->assertStringNotContainsString('edusharing_atto', $output);
        $this->assertStringContainsString('<div id="' . $id . '" class="edu_wrapper"', $output);
        $this->assertStringContainsString('data-node="node-quoted"', $output);
        $this->assertStringStartsWith('<p>before</p>', $output);
        $this->assertStringEndsWith('</p>', $output);
    }
}
