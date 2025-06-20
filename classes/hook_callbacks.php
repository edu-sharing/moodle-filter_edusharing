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

use core\hook\output\before_http_headers;
use Exception;
use mod_edusharing\UtilityFunctions;
use mod_edusharing\EduSharingService;

/**
 * Class hook_callbacks
 *
 * @author Marian Ziegler <ziegler@edu-sharing.net>
 * @package filter_edusharing
 * @copyright metaVentis GmbH — http://metaventis.com
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hook_callbacks {
    public static function before_http_headers(before_http_headers $hook): void {
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
        try {
            $repoUrl = rtrim($utils->get_config_entry('application_cc_gui_url'), '/');
        } catch (Exception $exception) {
            mtrace($exception);
            $repoUrl = '';
        }
        global $PAGE;
        if ($PAGE->cm || $PAGE->course || $PAGE->pagelayout !== 'popup') {
            $PAGE->requires->js_init_code("
                (function() {
                    var link = document.createElement('link');
                    link.rel = 'stylesheet';
                    link.href = '$repoUrl/web-components/rendering-service-amd/styles.css';
                    document.head.appendChild(link);
                })();
            ");
        }
    }
}
