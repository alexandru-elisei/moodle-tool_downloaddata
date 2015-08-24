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
 * Download configuration file functions
 *
 * @package    tool_downloadconfig
 * @copyright  2015 Alexandru Elisei
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/excellib.class.php');
require_once($CFG->libdir . '/coursecatlib.php');

define('DC_DATA_COURSES', 0);
define('DC_DATA_USERS', 1);

define('DC_FORMAT_CSV', 0);
define('DC_FORMAT_XLS', 1);

define('DC_XLS_COLUMN_WIDTH', 13);
define('DC_XLS_COURSES_WORKSHEET_NAME', 'courses');
define('DC_XLS_USERS_WORKSHEET_NAME', 'users');

// Custom column widths for the Excel file.
$dc_column_widths = array(
    'category_path' => 30
);

// Cache for roles.
$dc_rolescache = array();

// Output fields for courses in CSV format.
$dc_csv_courses_fields = array(
    'shortname',
    'fullname',
    'category_path'
);

// Output fields for users in CSV format.
$dc_csv_users_fields = array(
    'username',
    'firstname',
    'lastname',
    'email',
    'auth',
    'course'
);

// Overwrite values for user fields in CSV format.
$dc_csv_users_overwrite = array(
    'auth' => 'ldap'
);

// Output fields for courses in Excel format.
$dc_xls_courses_fields = $dc_csv_courses_fields;

// Output fields for users in Excel format.
$dc_xls_users_fields = $dc_csv_users_fields;

/**
 * Save requested data to a file in the Excel format. Right now, Moodle only 
 * supports Excel2007 format.
 *
 * @param constant $data the type of data to be saved.
 * @param string $output the location of the output file.
 * @param array of stdClass $contents the file contents.
 * @param array $options save options.
 * @param array $roles user roles.
 * @return MoodleExcelWorkbook
 */
function dc_save_to_excel($data, $output, $options, $contents = NULL, $roles = NULL) {
    global $dc_xls_courses_fields;
    global $dc_xls_users_fields;
    global $dc_column_widths;
    global $dc_rolescache;

    $workbook = new MoodleExcelWorkbook($output);
    if ($data == DC_DATA_COURSES) {
        $worksheet = DC_XLS_COURSES_WORKSHEET_NAME;
        $workbook->$worksheet = $workbook->add_worksheet($worksheet);

        $columns = $dc_xls_courses_fields;
        if (!empty($options['templatecourse'])) {
            $columns[] = 'templatecourse';
        }
        dc_print_column_names($columns, $workbook->$worksheet);

        $lastcolumn = count($columns) - 1;
        $workbook->$worksheet->set_row(0, NULL, array('h_align' => 'center'));
        $workbook->$worksheet->set_column(0, $lastcolumn, DC_XLS_COLUMN_WIDTH);
        dc_set_custom_widths($columns, $workbook->$worksheet);

        $row = 1;
        // Saving courses
        foreach($contents as $key => $course) {
            foreach ($columns as $column => $field) {
                $workbook->$worksheet->write($row, $column, $course->$field);
            }
            $row++;
        }
    } else if ($data == DC_DATA_USERS) {
        // Resolving column and worksheet names
        if ($options['withcourses']) {
            $coursecolumns = $dc_xls_courses_fields;
            if (!empty($options['templatecourse'])) {
                $coursecolumns[] = 'templatecourse';
            }
            $columns = array_merge($coursecolumns, $dc_xls_users_fields);
        } else {
            $colums = $dc_xls_users_fields;
        }
        if (!empty($options['templatecourse'])) {
            $colums[] = $options['templatecourse'];
        }

        // Formatting the worksheets
        $lastcolumn = count($columns) - 1;
        if (!$options['separatesheets']) {
            $worksheet = DC_XLS_USERS_WORKSHEET_NAME;
            $workbook->$worksheet = $workbook->add_worksheet($worksheet);
            dc_print_column_names($columns, $workbook->$worksheet);
            $workbook->$worksheet->set_row(0, NULL, array('h_align' => 'center'));
            $workbook->$worksheet->set_column(0, $lastcolumn, DC_XLS_COLUMN_WIDTH);
            dc_set_custom_widths($columns, $workbook->$worksheet);
        } else {
            foreach ($roles as $key => $role) {
                $worksheet = $role;
                $workbook->$worksheet = $workbook->add_worksheet($worksheet);
                dc_print_column_names($columns, $workbook->$worksheet);
                $workbook->$worksheet->set_row(0, NULL, array('h_align' => 'center'));
                $workbook->$worksheet->set_column(0, $lastcolumn, DC_XLS_COLUMN_WIDTH);
                dc_set_custom_widths($columns, $workbook->$worksheet);
            }
        }
        
        $courses = dc_get_data(DC_DATA_COURSES, $options);
        $row = 1;
       /*
       foreach ($courses as $key => $course) {
           $coursecontext = context_course::instance($course->id);

           foreach ($roles as $key => $role) {
               $usersfields = implode(',', $dc_xls_users_fields);
               $userinfo = get_role_users($dc_rolescache[$role], $coursecontext,
                                          false, $usersfields);
           }

           $column = 0;
           foreach ($coursecolumns as $key => $field) {
                $workbook->$worksheet->write($row, $column, $course->$field);
                if (isset($dc_column_widths[$field])) {
                    $workbook->$worksheet->set_column($column, $column,
                                            $dc_column_widths[$field]);
                }
                $column++;
            }
       }
        */
    }

    return $workbook;
}

/**
 * Save requested data to a comma separated values (CSV) file.
 *
 * @param constant $data the type of data to be saved.
 * @param string $output the location of the output file.
 * @param array of stdClass $contents the file contents.
 * @param array $options save options.
 * @param array $roles user roles.
 * @return csv_export_writer.
 */
function dc_save_to_csv($data, $output, $options, $contents = NULL, $roles = NULL) {
    global $dc_csv_courses_fields;
    global $dc_csv_users_fields;
    global $DB;

    $csv = new csv_export_writer($options['delimiter']);
    if ($data == DC_DATA_COURSES) {
        // Saving field names
        $fields = $dc_csv_courses_fields;
        if (!empty($options['templatecourse'])) {
            $fields[] = 'templatecourse';
        }
        $csv->add_data($fields);

        // Saving courses
        foreach ($contents as $key => $course) {
            $row = array();
            foreach ($fields as $key => $field) {
                $row[] = $course->$field;
            }
            $csv->add_data($row);
        }
    } else if ($data == DC_DATA_USERS) {
        if (empty($roles)) {
            fputs(STDERR, get_string('emptyroles', 'tool_downloadconfig') . "\n");
            die();
        }
        //$users = 
    }
    return $csv;
}

/**
 * Get the data to be saved to a file.
 *
 * @param constant $data the type of data.
 * @param array $options function options.
 * @return array the information.
 */
function dc_get_data($data, $options = NULL) {
    global $DB;

    $ret = array();
    if ($data == DC_DATA_COURSES) {
        $ret = $DB->get_records('course');
        // Ignoring course Moodle
        unset($ret[1]);
        foreach($ret as $key => $course) {
            $course->category_path = dc_resolve_category_path($course->category);
        }
        // Adding templatecourse field, if needed
        if (!empty($options['templatecourse'])) {
            $course->templatecourse = $options['templatecourse'];
        }
    }

    return $ret;
}

/**
 * Internal function to resolve category hierarchy.
 *
 * @param int $parentid the parent id.
 * @return string the category hierarchy.
 */
function dc_resolve_category_path($parentid)
{
    global $DB;

    $path = '';
    $resolved = false;
    while (!$resolved) {
        if ($parentid == '0') {
            $resolved = true;
        } else {
            $cat = $DB->get_record('course_categories', array('id' => $parentid));
            if (empty($path)) {
                $path = $cat->name;
            } else {
                $path = $cat->name . ' / ' . $path;
            }
            $parentid = $cat->parent;
        }
    }

    return $path;
}

/**
 * Validate and process cli specified user roles.
 *
 * @param string $roles the cli supplied list of roles.
 * @return array converted string $roles, NULL in case of error.
 */
function dc_resolve_roles($roles)
{
    global $dc_rolescache;

    $ret = array();
    if (empty($roles)) {
        return NULL;
    }
    $ret = explode(',', $roles);
    $roles = get_assignable_roles(context_course::instance(SITEID),
                                     ROLENAME_SHORT);
    foreach($ret as $key => $rolename) {
        $id = array_search($rolename, $roles);
        if (!$id) {
            return NULL;
        }
        $dc_rolescache[$rolename] = $id;
    }

    return $ret;
}

/**
 * Print the field names for Excel files.
 *
 * @param array $columns the column names.
 * @param MoodleExcelWorksheet $worksheet the worksheet.
 * @return void.
 */
function dc_print_column_names($columns, $worksheet)
{
    $firstrow = 0;
    $column = 0;
    foreach ($columns as $key => $name) {
        $worksheet->write($firstrow, $column, $name);
        $column++;
    }
}

/**
 * Set custom fields witdth for Excel files.
 *
 * @param array $columns column names.
 * @param MoodleExcelWorksheet @worksheet Excel worksheet.
 * @return void.
 */
function dc_set_custom_widths($columns, $worksheet) 
{
    global $dc_column_widths;

    foreach ($columns as $no => $name) {
        if (isset($dc_column_widths[$name])) {
            $worksheet->set_column($no, $no, $dc_column_widths[$name]);
        }
    }
}

