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
 * Download data configuration options.
 *
 * @package    tool_downloaddata
 * @copyright  2015 Alexandru Elisei
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Custom column widths for the Excel file.
$ADMIN_TOOL_DOWNLOADDATA_COLUMN_WIDTHS = array(
    'default' => 13,
    'category_path' => 30,
	'email' => 30,
	'username' => 16
);

// Output fields for courses in CSV format.
$ADMIN_TOOL_DOWNLOADDATA_COURSE_FIELDS_CSV = array(
    'shortname',
    'fullname',
	'category_path',
);

// Output fields for courses in Excel format.
$ADMIN_TOOL_DOWNLOADDATA_COURSE_FIELDS_XLS = $ADMIN_TOOL_DOWNLOADDATA_COURSE_FIELDS_CSV;

// Output fields for users in CSV format.
$ADMIN_TOOL_DOWNLOADDATA_USER_FIELDS_CSV = array(
    'username',
    'firstname',
    'lastname',
    'email',
    'auth'
);

// Output fields for users in Excel format.
$ADMIN_TOOL_DOWNLOADDATA_USER_FIELDS_XLS = $ADMIN_TOOL_DOWNLOADDATA_USER_FIELDS_CSV;

// Default worksheet names, when not using separate sheets.
$ADMIN_TOOL_DOWNLOADATA_WORKSHEET_NAMES = array(
    'users' => 'users',
    'courses' => 'courses',
);

// Overwrite values for course fields.
$ADMIN_TOOL_DOWNLOADDATA_COURSE_OVERWRITES = array();

// Overwrite values for user fields.
$ADMIN_TOOL_DOWNLOADDATA_USER_OVERWRITES = array();
