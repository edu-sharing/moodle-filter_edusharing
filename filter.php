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
 * Filter converting edu-sharing URIs in the text to edu-sharing rendering links
 *
 * @package filter_edusharing
 * @copyright metaVentis GmbH — http://metaventis.com
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use filter_edusharing\FilterLogic;

defined('MOODLE_INTERNAL') || die();

global $CFG;

/**
 * Parse content for edu-sharing objects to render them
 *
 * @copyright metaVentis GmbH — http://metaventis.com
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */
class filter_edusharing extends moodle_text_filter {

    /**
     * Function filter
     *
     * @param string $text to be processed by the text
     * @param array $options filter options
     *
     * @return string text after processing
     * @see filter_manager::apply_filter_chain()
     */
    public function filter($text, array $options = []): string {
        try {
            if (empty(get_config('edusharing', 'application_cc_gui_url'))) {
                return $text;
            }
            $logic = new FilterLogic();
        } catch (Exception $exception) {
            debugging($exception->getMessage());
            return $text;
        }
        return $logic->apply_filter($text, $options);
    }
}
