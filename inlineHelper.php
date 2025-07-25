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
 * inlineHelper
 *
 * @package filter_edusharing
 * @copyright metaVentis GmbH — http://metaventis.com
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
use filter_edusharing\FilterUtilities;

require_once(dirname(__FILE__) . '/../../config.php');

$filterutils = new FilterUtilities();
try {
    require_login((string)optional_param('containerId', '', PARAM_TEXT));
    $redirecturl = $filterutils->get_redirect_url();
    redirect($redirecturl);
} catch (Exception $exception) {
    echo $exception->getMessage();
    exit;
}
