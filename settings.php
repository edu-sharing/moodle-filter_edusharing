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
 * edu-sharing filter settings
 *
 * @package    filter_edusharing
 * @copyright  metaVentis GmbH — http://metaventis.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $ADMIN;

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_configmulticheckbox('filter_edusharing/formats',
            get_string('settingformats', 'filter_edusharing'),
            get_string('settingformats_desc', 'filter_edusharing'),
            [FORMAT_MOODLE => 1, FORMAT_HTML => 1], format_text_menu()));
    $settings->add(
        new admin_setting_configcheckbox('filter_edusharing/enable_rendering_2',
            new lang_string('enable_rendering_2', 'filter_edusharing'),
            new lang_string('enable_rendering_2_help', 'filter_edusharing'),
            '0'
        )
    );
}
