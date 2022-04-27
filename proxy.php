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

/**
 * Proxy script for ajax based rendering
 *
 * @package filter_edusharing
 * @copyright metaVentis GmbH — http://metaventis.com
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
global $CFG;

require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/../../mod/edusharing/lib.php');


/**
 * Class for ajax based rendering
 *
 * @copyright metaVentis GmbH — http://metaventis.com
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class filter_edusharing_edurender {

    /**
     * Get rendered object via curl
     *
     * @param string $url
     * @return string
     * @throws Exception
     */
    public function filter_edusharing_get_render_html($url) {
        $curl = new curl();
        $curl->setopt( array(
            'CURLOPT_SSL_VERIFYPEER' => false,
            'CURLOPT_SSL_VERIFYHOST' => false,
            'CURLOPT_FOLLOWLOCATION' => 1,
            'CURLOPT_HEADER' => 0,
            'CURLOPT_RETURNTRANSFER' => 1,
            'CURLOPT_USERAGENT' => $_SERVER['HTTP_USER_AGENT'],
        ));

        $inline = $curl->get($url);

        if ($curl->error) {
            return get_string('error_curl', 'filter_edusharing', get_config('edusharing', 'application_appname')) . $curl->error;
            debugging('cURL Error: '.$curl->error);
            trigger_error('cURL Error: '.$curl->error);
            exit();
        }
        return $inline;
    }

    /**
     * Prepare rendered object for display
     *
     * @param string $html
     */
    public function filter_edusharing_display($html) {
        global $CFG;
        require_once($CFG->dirroot . '/mod/edusharing/lib/EduSharingService.php');

        error_reporting(0);
        $resid = required_param('resId', PARAM_INT);

        $html = str_replace(array("\n", "\r", "\n"), '', $html);

        /*
         * replaces {{{LMS_INLINE_HELPER_SCRIPT}}}
         */
        $html = str_replace("{{{LMS_INLINE_HELPER_SCRIPT}}}",
                $CFG->wwwroot . "/filter/edusharing/inlineHelper.php?sesskey=".sesskey()."&resId=" . $resid, $html);

        $html = preg_replace("/<es:title[^>]*>.*<\/es:title>/Uims", utf8_decode(optional_param('title', '', PARAM_TEXT)), $html);

        if (strpos($html, 'data-es-auth-required=true') !== false){
            $eduSharingService = new EduSharingService();
            $ticket = $eduSharingService->getTicket();
            $html = str_replace('data-es-auth-required=true', 'ticket='.$ticket.'"', $html);
        }

        $caption = utf8_decode(optional_param('caption', '', PARAM_TEXT));
        if($caption)
            $html .= '<p class="caption">' . $caption . '</p>';

        echo $html;
        exit();
    }
}

$url = required_param('URL', PARAM_NOTAGS);
$parts = parse_url($url);
parse_str($parts['query'], $query);
require_login($query['course_id']);
require_sesskey();

$ts = $timestamp = round(microtime(true) * 1000);
$url .= '&ts=' . $ts;
$url .= '&sig=' . urlencode(edusharing_get_signature(get_config('edusharing', 'application_appid') . $ts . $query['obj_id']));
$url .= '&signed=' . urlencode(get_config('edusharing', 'application_appid') . $ts . $query['obj_id']);
$url .= '&videoFormat=' . optional_param('videoFormat', '', PARAM_TEXT);

$e = new filter_edusharing_edurender();
$html = $e->filter_edusharing_get_render_html($url);
$e->filter_edusharing_display($html);
