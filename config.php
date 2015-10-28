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
$dd_custom_column_widths = array(
    'category_path' => 30,
	'email' => 30,
	'username' => 16
);

// Output fields for courses in CSV format.
$dd_csv_courses_fields = array(
    'shortname',
    'fullname',
	'category_path',
);

// Output fields for courses in Excel format.
$dd_xls_courses_fields = $dd_csv_courses_fields;

// Output fields for users in CSV format.
$dd_csv_users_fields = array(
    'username',
    'firstname',
    'lastname',
    'email',
    'auth'
);

// Output fields for users in Excel format.
$dd_xls_users_fields = $dd_csv_users_fields;

// Overwrite values for users fields.
$dd_users_overwrite = array(
);

// Overwrite values for courses fields.
$dd_courses_overwrite = array(
);
