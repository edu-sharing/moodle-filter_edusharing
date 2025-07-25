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

use mod_edusharing\EduSharingService;
use mod_edusharing\UtilityFunctions;

/**
 * lib
 *
 * @package filter_edusharing
 * @copyright metaVentis GmbH — http://metaventis.com
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Function filter_edusharing_before_http_headers
 *
 * Prepares and injects necessary EduSharing rendering service CSS into the page's HTTP headers.
 * This function ensures that the required tables, internal URLs, and rendering service 2 are available
 * before injecting the CSS file for the rendering service. It attaches the CSS file only if the
 * page context is appropriate (excluding popups).
 *
 * @return void
 */
function filter_edusharing_before_http_headers() {
    global $DB;
    $tables = $DB->get_tables();
    if (! in_array('config', $tables)) {
        return;
    }
    $utils = new UtilityFunctions();
    if (empty($utils->get_internal_url())) {
        return;
    }
    $service = new EduSharingService();
    if (!$service->has_rendering_2()) {
        return;
    }
    $utils = new UtilityFunctions();
    global $PAGE;
    if ($PAGE->cm || $PAGE->course || $PAGE->pagelayout !== 'popup') {
        try {
            $repourl = rtrim($utils->get_config_entry('application_cc_gui_url'), '/');
            $PAGE->requires->js_init_code("
                (function() {
                    var link = document.createElement('link');
                    link.rel = 'stylesheet';
                    link.href = '$repourl/web-components/rendering-service-amd/styles.css';
                    document.head.appendChild(link);
                })();
            ");
        } catch (Exception $codingexception) {
            mtrace($codingexception);
        }
    }
}
