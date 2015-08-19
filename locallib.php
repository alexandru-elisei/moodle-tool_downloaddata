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

define('DC_INVALID_ROLES', 1);

// Custom column widths for the Excel file.
$dc_column_widths = array(
    'category_path' => 30,
    'email' => 30
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
    'auth'
);

// Overwrite values for user fields in CSV format.
$dc_csv_users_overwrite = array(
    'auth' => 'ldap'
);

// Output fields for courses in Excel format.
$dc_xls_courses_fields = $dc_csv_courses_fields;

// Output fields for users in Excel format.
$dc_xls_users_fields = $dc_csv_users_fields;

// Overwrite values for user fields in Excel format.
$dc_xls_users_overwrite = $dc_csv_users_overwrite;

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
function dc_save_to_excel($data, $output, $options, $contents, $roles = NULL) {
    global $DB;
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

        $lastcolumnindex = count($columns) - 1;
        $workbook->$worksheet->set_column(0, $lastcolumnindex, DC_XLS_COLUMN_WIDTH);
        dc_set_custom_widths($columns, $workbook->$worksheet);

        $row = 1;
        // Saving courses
        foreach ($contents as $key => $course) {
            foreach ($columns as $column => $field) {
                $workbook->$worksheet->write($row, $column, $course->$field);
            }
            $row++;
        }
    } else if ($data == DC_DATA_USERS) {
        $worksheet = DC_XLS_USERS_WORKSHEET_NAME;
        $workbook->$worksheet = $workbook->add_worksheet($worksheet);

        $lastcolumnindex = 0;
        $row = 1;
        foreach ($contents as $key => $user) {
            // Print user info only if their role was requested (or printing 
            // all users).
            if (!empty($user->roles)) {
                $column = 0;
                $columns = $dc_xls_users_fields;
                foreach ($dc_xls_users_fields as $key => $field) {
                    $workbook->$worksheet->write($row, $column, $user->$field);
                    $column++;
                }
                // Saving course and role fields, if necessary.
                foreach ($user->roles as $key => $rolearr) {
                    foreach ($rolearr as $role => $course) {
                        $workbook->$worksheet->write($row, $column, $course);
                        $column++;
                        $workbook->$worksheet->write($row, $column, $role);
                        $column++;
                    }
                }
                if ($column-1 > $lastcolumnindex) {
                    $lastcolumnindex = $column-1;
                }
                $row++;
            }
        }

        // Getting column names.
        $columns = $dc_xls_users_fields;
        $columnindex = count($columns) - 1;
        if ($lastcolumnindex > $columnindex) {
            $rolenumber = 1;
            for ($i = $columnindex + 1; $i <= $lastcolumnindex; $i += 2) {
                $coursecolumn = 'course' . $rolenumber;
                $columns[] = $coursecolumn;
                $rolecolumn = 'role' . $rolenumber;
                $columns[] = $rolecolumn;
                $rolenumber++;
            }
        }
        dc_print_column_names($columns, $workbook->$worksheet);
        dc_set_column_widths($columns, $workbook->$worksheet);
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
    }
    return $csv;
}

/**
 * Get the courses to be saved to a file.
 *
 * @param array $options function options.
 * @return array of stdClass the courses.
 */
function dc_get_courses($options = array()) {
    global $DB;

    $courses = $DB->get_records('course');
    // Ignoring course Moodle
    foreach ($courses as $key => $course) {
        if ($course->shortname == 'moodle') {
            unset($courses[$key]);
            break;
        }
    }
    foreach ($courses as $key => $course) {
        $course->category_path = dc_resolve_category_path($course->category);
    }
    // Adding templatecourse field, if needed
    if (isset($options['templatecourse'])) {
        $course->templatecourse = $options['templatecourse'];
    }

    return $courses;
}

/**
 * Returns all the users to be saved to file.
 *
 * @param array $roles the requested roles.
 * @param array $options function options.
 * @return array of stdClass the users.
 */
function dc_get_users($roles, $options = array()) {
    global $DB;
    global $dc_rolescache;
    global $dc_xls_users_fields;
    global $dc_xls_users_overwrite;

    $courses = dc_get_courses($options);
    $users = $DB->get_records('user', array('deleted' => '0'));


    // Course context cache.
    $cccache = array();
    // Adding role and course specific fields for printing.
    foreach ($users as $key => $user) {
        // Discarding admin and guest users from the users list
        if (is_siteadmin($user->id)) {
            unset($users[$key]);
            continue;
        } else if ($user->username == 'guest') {
            unset($users[$key]);
            continue;
        }

        // All the user's roles, array of items like $role => $course
        $userroles = array();
        $hasrequestedroles = false;
        // Error if users and roles haven't been prepared beforehand.
        if (empty($dc_rolescache)) {
            fputs(STDERR, "Empty dc_rolescache!" . "\n");
            die();
        }
        foreach ($courses as $key => $course) {
            // Building course context cache.
            if (!isset($cccache[$course->id])) {
                $cccache[$course->id] = context_course::instance($course->id);
            }
            foreach ($dc_rolescache as $role => $roleid) {
                $hasrole = user_has_role_assignment($user->id, $roleid,
                    $cccache[$course->id]->id);
                if ($hasrole) {
                    $userroles[] = array($role => $course->shortname);
                    if (in_array($role, $roles)) {
                        $hasrequestedroles = true;
                    }
                }
            }
        }
        // Saving all the users roles if one of his roles was specified.
        if ($hasrequestedroles) {
            $user->roles = $userroles;
        } else {
            // All users was requested.
            if (empty($roles)) {
                $user->roles = $userroles;
            // User doesn't have any of the specified roles.
            } else {
                $user->roles = array();
            }
        }

        if (isset($options['useoverwrites'])) {
            foreach ($dc_xls_users_overwrite as $field => $value) {
                $user->$field = $value;
            }
        }
    }

    return $users;
}

/**
 * Internal function to resolve category hierarchy.
 *
 * @param int $parentid the parent id.
 * @return string the category hierarchy.
 */
function dc_resolve_category_path($parentid) {
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
 * @return array converted string $roles.
 */
function dc_resolve_roles($roles) {
    global $dc_rolescache;

    $ret = explode(',', $roles);
    $allroles = get_all_roles();
    // Building roles cache.
    foreach ($allroles as $key => $role) {
        $dc_rolescache[$role->shortname] = $role->id;
    }

    // Returning all roles if none were specified
    if (empty($roles)) {
        $ret = array();
        foreach ($allroles as $key => $role) {
            $ret[] = $role->shortname;
        }
    } else {
        // Checking for invalid roles
        foreach ($ret as $key => $role) {
            if (!isset($dc_rolescache[$role])) {
                return DC_INVALID_ROLES;
            }
        }
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
function dc_print_column_names($columns, $worksheet) {
    $firstrow = 0;
    $column = 0;
    foreach ($columns as $key => $name) {
        $worksheet->write($firstrow, $column, $name);
        $column++;
    }
    $worksheet->set_row($firstrow, NULL, array('h_align' => 'right'));
}

/**
 * Set file column widths for Excel files.
 *
 * @param array $columns column names.
 * @param MoodleExcelWorksheet @worksheet Excel worksheet.
 * @return void.
 */
function dc_set_column_widths($columns, $worksheet) {
    global $dc_column_widths;

    $lastcolumnindex = count($columns)-1;
    $worksheet->set_column(0, $lastcolumnindex, DC_XLS_COLUMN_WIDTH);
    foreach ($columns as $no => $name) {
        if (isset($dc_column_widths[$name])) {
            $worksheet->set_column($no, $no, $dc_column_widths[$name]);
        }
    }
}
