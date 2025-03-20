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

namespace filter_edusharing;

use coding_exception;
use dml_exception;
use DOMDocument;
use Exception;
use mod_edusharing\Constants;
use mod_edusharing\EduSharingService;
use mod_edusharing\UtilityFunctions;
use stdClass;

/**
 * Class FilterLogic
 *
 * @author Marian Ziegler <ziegler@edu-sharing.net>
 * @package filter_edusharing
 * @copyright metaVentis GmbH — http://metaventis.com
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class FilterLogic {
    /**
     * @var UtilityFunctions|null
     */
    private ?UtilityFunctions  $utils;
    /**
     * @var EduSharingService|null
     */
    private ?EduSharingService $service;

    /**
     * FilterLogic constructor
     *
     * @param UtilityFunctions|null $utils
     * @param EduSharingService|null $service
     */
    public function __construct(?UtilityFunctions $utils = null, ?EduSharingService $service = null) {
        $this->utils   = $utils;
        $this->service = $service;
        $this->init();
    }

    /**
     * Function init
     *
     * @return void
     */
    private function init(): void {
        if ($this->utils === null) {
            $this->utils = new UtilityFunctions();
        }
        if ($this->service === null) {
            $this->service = new EduSharingService();
        }
    }

    /**
     * Function apply_filter
     *
     * @param string $text
     * @param array $options
     * @return string
     */
    public function apply_filter(string $text, array $options): string {
        global $PAGE, $CFG;
        global $edusharingfilterloaded;
        if (!isset($options['originalformat']) || !str_contains($text, 'edusharing_atto')) {
            return $text;
        }
        $memento = $text;
        try {
            $this->service->require_edu_login(checksessionkey: false);
            $esmatches = $this->utils->get_inline_object_matches($text);
            if (!empty($esmatches)) {
                // Disable page-caching to "renew" render-session-data.
                $PAGE->set_cacheable(false);
                if (!$edusharingfilterloaded) {
                    $PAGE->requires->js_call_amd('filter_edusharing/edu', 'start');
                    $PAGE->requires->js_call_amd('esrendering', 'init');

                    $edusharingfilterloaded = true;
                }
                foreach ($esmatches as $match) {
                    $text = str_replace($match, $this->convert_object($match), $text);
                }
            }
        } catch (Exception $exception) {
            trigger_error($exception->getMessage(), E_USER_WARNING);
            return $memento;
        }
        return $text;
    }

    /**
     * Function convert_object
     *
     * Prepare object for rendering, wrap rendered object
     *
     * @param string $object
     * @return string
     * @throws coding_exception
     * @throws dml_exception
     * @throws Exception
     */
    private function convert_object(string $object): string {
        global $DB;
        libxml_use_internal_errors(true);
        $doc = new DOMDocument();
        $doc->loadHTML($object);
        $errors = libxml_get_errors();
        if (!empty($errors)) {
            debugging("Error parsing ES object: " . $object);
            return '';
        }
        $node = $doc->getElementsByTagName('a')->item(0);
        if (empty($node)) {
            $node = $doc->getElementsByTagName('img')->item(0);
            $qs   = $node->getAttribute('src');
        } else {
            $qs = $node->getAttribute('href');
        }
        if (empty($node)) {
            trigger_error(get_string('error_loading_node', 'filter_edusharing'), E_USER_WARNING);
            return '';
        }
        $queryparams = parse_url($qs, PHP_URL_QUERY);
        if ($queryparams === null) {
            return get_string('error_parsing_queryparams');
        }
        parse_str($queryparams, $params);
        $edusharing                = $DB->get_record(
            Constants::EDUSHARING_TABLE,
            ['id' => (int)$params['resourceId']],
            '*',
            MUST_EXIST
        );
        $height                    = $node->getAttribute('height');
        $width                     = $node->getAttribute('width');
        $renderparams['height']    = $height;
        $renderparams['width']     = $width;
        $renderparams['title']     = $node->getAttribute('title');
        $renderparams['mimetype']  = $params['mimetype'];
        $renderparams['mediatype'] = $params['mediatype'];
        $renderparams['caption']   = $params['caption'];
        $converted                 = $this->render_inline($edusharing, $renderparams);
        $wrapperattributes[]       = 'id="' . $params['resourceId'] . '"';
        $wrapperattributes[]       = 'class="edu_wrapper"';
        if (str_contains($renderparams['mimetype'], 'image')) {
            $wrapperattributes[] = 'data-id="' . $params['resourceId'] . '"';
        }
        $nodestyle           = $node->getAttribute('style');
        $styleattr           = match (true) {
            strpos($nodestyle, 'left') > -1 => 'display: block; float: left; margin: 0 14px 14px 0;',
            strpos($nodestyle, 'right') > -1 => 'display: block; float: right; margin: 0 0 14px 14px;',
            $renderparams['mediatype'] === 'directory' || $renderparams['mediatype'] === 'folder'
                => 'display: block; margin: 14px 0;',
            default => 'display: inline-block; margin: 14px 0;'
        };
        $wrapperattributes[] = 'style="' . $styleattr . '"';
        return '<div ' . implode(' ', $wrapperattributes) . '>' . $converted . '</div>';
    }

    /**
     * Function render_inline
     *
     * @param stdClass $edusharing
     * @param array $renderparams
     * @return string
     * @throws Exception
     *
     */
    private function render_inline(stdClass $edusharing, array $renderparams): string {
        global $CFG, $COURSE;
        $objecturl = $edusharing->object_url ?? '';
        if (empty($objecturl)) {
            throw new Exception(get_string('error_empty_object_url', 'filter_edusharing'));
        }
        $utils  = new UtilityFunctions();
        $url    = $utils->get_redirect_url($edusharing, Constants::EDUSHARING_DISPLAY_MODE_INLINE);
        $url    .= '&height=' . urlencode($renderparams['height']) . '&width=' . urlencode($renderparams['width']);
        if  (get_config('filter_edusharing', 'enable_rendering_2' === '1')) {
            $nodeid = $this->utils->get_object_id_from_url($objecturl);
            return '<div class="eduContainer" data-type="esObject" data-node="' . $nodeid . '">' .
                '<div class="edusharing_spinner_inner"><div class="edusharing_spinner1"></div></div>' .
                '<div class="edusharing_spinner_inner"><div class="edusharing_spinner2"></div></div>' .
                '<div class="edusharing_spinner_inner"><div class="edusharing_spinner3"></div></div>' .
                'edu sharing object</div>';
        }
        $inline = '<div class="eduContainer" data-type="esObject" data-url="' . $CFG->wwwroot .
            '/filter/edusharing/proxy.php?sesskey=' . sesskey() . '&URL=' . urlencode($url) . '&resId=' .
            $edusharing->id . '&title=' . urlencode($renderparams['title']) .
            '&mimetype=' . urlencode($renderparams['mimetype']) .
            '&mediatype=' . urlencode($renderparams['mediatype']) .
            '&caption=' . urlencode($renderparams['caption']) .
            '&course_id=' . urlencode($COURSE->id) . '">' .
            '<div class="edusharing_spinner_inner"><div class="edusharing_spinner1"></div></div>' .
            '<div class="edusharing_spinner_inner"><div class="edusharing_spinner2"></div></div>' .
            '<div class="edusharing_spinner_inner"><div class="edusharing_spinner3"></div></div>' .
            'edu sharing object</div>';
        // Amd-js is not being loaded in format_tiles-plugin, so we add it here.
        if ($COURSE->format == 'tiles') {
            $inline .= '<script src="' . $CFG->wwwroot . '/filter/edusharing/fallback.js"></script>';
        }
        return $inline;
    }
}
