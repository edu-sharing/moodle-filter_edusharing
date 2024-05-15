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
use EduSharingApiClient\NodeDeletedException;
use EduSharingApiClient\Usage;
use EduSharingApiClient\UsageDeletedException;
use Exception;
use JsonException;
use mod_edusharing\Constants;
use mod_edusharing\EduSharingService;
use mod_edusharing\EduSharingUserException;
use mod_edusharing\UtilityFunctions;
use moodle_exception;
use stdClass;

defined('MOODLE_INTERNAL') || die();
require_once(dirname(__FILE__) . '/../../../mod/edusharing/lib.php');

/**
 * Class FilterUtilities
 *
 * @author Marian Ziegler <ziegler@edu-sharing.net>
 * @package filter_edusharing
 */
class FilterUtilities {
    /**
     * @var EduSharingService|null
     */
    private ?EduSharingService $service;
    /**
     * @var UtilityFunctions|null
     */
    private ?UtilityFunctions  $utils;

    /**
     * FilterUtilities constructor
     *
     * @param EduSharingService|null $service
     * @param UtilityFunctions|null $utils
     */
    public function __construct(?EduSharingService $service = null, ?UtilityFunctions $utils = null) {
        $this->service = $service;
        $this->utils   = $utils;
        $this->init();
    }

    /**
     * Function init
     *
     * @return void
     */
    private function init(): void {
        if ($this->service === null) {
            $this->service = new EduSharingService();
        }
        if ($this->utils === null) {
            $this->utils = new UtilityFunctions();
        }
    }

    /**
     * Function get_redirect_url
     *
     * @return string
     * @throws coding_exception
     * @throws moodle_exception
     * @throws Exception
     */
    public function get_redirect_url(): string {
        return $this->service->get_redirect_url(new Usage(
            (string)optional_param('nodeId', null, PARAM_TEXT),
            optional_param('nodeVersion', null, PARAM_TEXT),
            (string)optional_param('containerId', null, PARAM_TEXT),
            (string)optional_param('resourceId', null, PARAM_TEXT),
            (string)optional_param('usageId', null, PARAM_TEXT)
        ), $this->utils->get_auth_key());
    }

    /**
     * Function getHtml
     *
     * @return string
     * @throws EduSharingUserException
     * @throws NodeDeletedException
     * @throws UsageDeletedException
     * @throws JsonException
     * @throws coding_exception
     * @throws dml_exception
     * @throws Exception
     */
    public function get_html(): string {
        global $DB;
        $url   = required_param('URL', PARAM_NOTAGS);
        $parts = parse_url($url);
        parse_str($parts['query'], $query);
        $resourceid = null;
        if (!empty($query['resource_id'])) {
            $resourceid = $query['resource_id'];
        } else {
            try {
                $resourceid = required_param('resource_id', PARAM_NOTAGS);
            } catch (Exception $exception) {
                unset($exception);
            }
        }
        if ($resourceid !== null) {
            $edusharing = $DB->get_record(Constants::EDUSHARING_TABLE, ['id' => $resourceid], '*', MUST_EXIST);
        } else {
            throw new EduSharingUserException('edusharing resource id missing in URL or GET');
        }
        $usageid = $edusharing->usage_id;
        if (empty($usageid)) {
            $usagedata = new stdClass();
            $usagedata->ticket      = $this->service->get_ticket();
            $usagedata->nodeId      = $query['obj_id'];
            $usagedata->containerId = (string)$edusharing->course;
            $usagedata->resourceId  = (string)$edusharing->id;
            $usageid                = $this->service->get_usage_id($usagedata);
            $edusharing->usage_id   = $usageid;
            $DB->update_record('edusharing', $edusharing);
        }
        $renderparams = ['width' => $query['width'], 'height' => $query['height']];
        $node = $this->service->get_node(new Usage(
            $query['obj_id'],
            $edusharing->object_version === '0' ? null : $edusharing->object_version,
            (string)$edusharing->course,
            (string)$edusharing->id,
            $usageid
        ), $renderparams, $this->utils->get_auth_key());
        return $node['detailsSnippet'];
    }
}
