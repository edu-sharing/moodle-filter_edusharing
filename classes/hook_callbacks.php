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

/**
 * Class hook_callbacks
 *
 * @author Marian Ziegler <ziegler@edu-sharing.net>
 * @package filter_edusharing
 * @copyright metaVentis GmbH — http://metaventis.com
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hook_callbacks {
    /**
     * Function before_http_headers
     *
     * This callback no longer does anything and is not registered any more. It only remains so that a
     * hook callback cache built before the upgrade can still call it without a fatal error.
     *
     * @deprecated since 11.0.4 - remove in 12.0
     * @param before_http_headers $hook The hook instance associated with the current execution of the hook.
     * @return void
     */
    public static function before_http_headers(before_http_headers $hook): void {
    }
}
