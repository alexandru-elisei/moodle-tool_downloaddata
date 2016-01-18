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

/**
 * Download data file functions.
 *
 * @package    tool_downloaddata
 * @copyright  2015 Alexandru Elisei
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Process override fields given as a comma-separated list of field=override pairs.
 *
 * @throws coding_exception.
 * @param string $rawoverrides Comma-separated list of 'field=override' pairs.
 * @return string[] Array of override fields and their values.
 */
function tool_downloaddata_process_overrides($rawoverrides) {
    if (empty($rawoverrides)) {
        throw new coding_exception(get_string('emptyoverrides', 'tool_downloaddata'));
    }
    $processedoverrides = array();
    $o = explode(',', $rawoverrides);
    foreach ($o as $value) {
        $override = explode('=', $value);
        $processedoverrides[trim($override[0])] = trim($override[1]);
    }

    return $processedoverrides;
}

/**
 * Process fields given as a comma-separated list of field names.
 *
 * @throws coding_exception.
 * @param string $rawfields Comma-separated list of field names.
 * @return string[] The array of field names.
 */
function tool_downloaddata_process_fields($rawfields) {
    if (empty($rawfields)) {
        throw new coding_exception(get_string('emptyfields', 'tool_downloaddata'));
    }

    $processedfields = explode(',', $rawfields);
    foreach ($processedfields as $key => $field) {
        $processedfields[$key] = trim($field);
    }

    return $processedfields;
}

function tool_downloaddata_process_roles($rawroles) {
    if (empty($rawroles)) {
        throw new coding_exception(get_string('emptyroles', 'tool_downloaddata'));
    }

    $processedroles = explode(',', $rawroles);
    foreach ($processedroles as $key => $roles) {
        $processedroles[$key] = trim($roles);
    }

    return $processedroles;
}
