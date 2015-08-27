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
$dc_custom_column_widths = array(
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

// Overwrite values for users fields.
$dc_users_overwrite = array(
    'auth' => 'ldap'
);

// Overwrite values for courses fields.
$dc_courses_overwrite = array(
    'templatecourse' => 'template',
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
 * @return class MoodleExcelWorkbook
 */
function dc_save_to_excel($data, $output, $options, $contents, $roles = NULL) {
    global $DB;
    global $dc_xls_courses_fields;
    global $dc_xls_users_fields;
    global $dc_custom_column_widths;
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
        dc_set_column_widths($columns, $workbook->$worksheet);

        $row = 1;
        // Saving courses
        foreach ($contents as $key => $course) {
            foreach ($columns as $column => $field) {
                $workbook->$worksheet->write($row, $column, $course->$field);
            }
            $row++;
        }
    } else if ($data == DC_DATA_USERS) {
        $worksheets = array();
        // Current row for each worksheet.
        $worksheetrow = array();
        $lastcolumnindex = array();
        if ($options['separatesheets']) {
            foreach ($roles as $key => $role) {
                $sheetname = $role;
                $worksheets[] = $sheetname;
                $workbook->$sheetname = $workbook->add_worksheet($sheetname);
                $worksheetrow[$sheetname] = 1;
                $lastcolumnindex[$sheetname] = 0;
            }
        } else {
            $sheetname = DC_XLS_USERS_WORKSHEET_NAME;
            $worksheets[] = $sheetname;
            $workbook->$sheetname = $workbook->add_worksheet($sheetname);
            $worksheetrow[$sheetname] = 1;
            $lastcolumnindex[$sheetname] = 0;
        }

        foreach ($contents as $key => $user) {
            // Print user info only if their role was requested.
            if (!empty($user->roles)) {
                // Print all users on one worksheet.
                if (!$options['separatesheets']) {
                    $sheetname = reset($worksheets);
                    $column = 0;
                    foreach ($dc_xls_users_fields as $key => $field) {
                        $workbook->$sheetname->write($worksheetrow[$sheetname], 
                                                     $column, $user->$field);
                        $column++;
                    }

                    // Saving course and role fields
                    foreach ($user->roles as $key => $rolearray) {
                        foreach ($rolearray as $role => $course) {
                            $workbook->$sheetname->write($worksheetrow[$sheetname], 
                                                         $column, $course);
                            $column++;
                            $workbook->$sheetname->write($worksheetrow[$sheetname], 
                                                         $column, $role);
                            $column++;
                        }
                    }

                    $worksheetrow[$sheetname]++;
                    if ($lastcolumnindex[$sheetname] < $column-1) {
                        $lastcolumnindex[$sheetname] = $column-1;
                    }
                } else {
                    // Use separate worksheets for each role.
                    foreach ($roles as $key => $role) {
                        $sheetname = $role;
                        $column = 0;

                        $hasrole = false;
                        foreach ($user->roles as $key => $rolearray) {
                            if (isset($rolearray[$role])) {
                                $hasrole = true;
                                break;
                            }
                        }
                        if ($hasrole) {
                            foreach ($dc_xls_users_fields as $key => $field) {
                                $workbook->$role->write($worksheetrow[$sheetname], 
                                                        $column, $user->$field);
                                $column++;
                            }
                            foreach ($user->roles as $key => $rolearray) {
                                foreach ($rolearray as $r => $c) {
                                    $workbook->$sheetname->write($worksheetrow[$sheetname], 
                                        $column, $c);
                                    $column++;
                                    $workbook->$sheetname->write($worksheetrow[$sheetname],
                                        $column, $r);
                                    $column++;
                                }
                            }
                            $worksheetrow[$sheetname]++;
                            if ($lastcolumnindex[$sheetname] < $column - 1) {
                                $lastcolumnindex[$sheetname] = $column - 1;
                            }
                        }
                    }
                }
            }
        }

        // Getting column names for each worksheet.
        foreach ($worksheets as $key => $worksheet) {
            $columns = $dc_xls_users_fields;
            $columnindex = count($columns) - 1;
            if ($lastcolumnindex[$worksheet] > $columnindex) {
                $rolenumber = 1;
                for ($i = $columnindex + 1; $i <= $lastcolumnindex[$worksheet]; $i += 2) {
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
 * @return class csv_export_writer.
 */
function dc_save_to_csv($data, $output, $options, $contents, $roles = NULL) {
    global $dc_csv_courses_fields;
    global $dc_csv_users_fields;
    global $dc_users_overwrite;
    global $dc_courses_overwrite;
    global $DB;

    $csv = new csv_export_writer($options['delimiter']);
    $csv->set_filename($output);
    if ($data == DC_DATA_COURSES) {
        // Saving field names
        $fields = $dc_csv_courses_fields;
        if ($options['useoverwrites']) {
            foreach ($dc_courses_overwrite as $field => $value) {
                $fields[] = $field;
            }
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
        $maxrolesnumber = 0;
        foreach ($contents as $key => $user) {
            $rolesnumber = count($user->roles);
            if ($rolesnumber > $maxrolesnumber) {
                $maxrolesnumber = $rolesnumber;
            }
        }

        // Saving field names
        $row = $dc_csv_users_fields;
        if ($maxrolesnumber > 0) {
            for ($i = 1; $i <= $maxrolesnumber; $i++) {
                $coursename = 'course' . $i;
                $rolename = 'role' . $i;
                $row[] = $coursename;
                $row[] = $rolename;
            }
        }
        $csv->add_data($row);
        $columnsnumber = count($row);

        foreach ($contents as $key => $user) {
            if (!empty($user->roles)) {
                $row = array();
                foreach ($dc_csv_users_fields as $key => $field) {
                    $row[] = $user->$field;
                }
                foreach ($user->roles as $key => $rolearray) {
                    foreach($rolearray as $role => $course) {
                        $row[] = $course;
                        $row[] = $role;
                    }
                }
                $no = count($row);
                // Adding blank columns until we have the same number of 
                // columns.
                while ($no < $columnsnumber) {
                    $row[] = '';
                    $no++;
                }
                $csv->add_data($row);
            }
        }
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
    global $dc_courses_overwrite;

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
        // Adding overwrite fields and values.
        if ($options['useoverwrites']) {
            foreach ($dc_courses_overwrite as $field => $value) {
                $course->$field = $value;
            }
        }
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
    global $dc_users_overwrite;

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

        // Error if users and roles haven't been prepared beforehand.
        if (empty($dc_rolescache)) {
            fputs(STDERR, "Empty dc_rolescache!" . "\n");
            die();
        }

        // All the user's roles, array of items like $role => $course
        $userroles = array();
        $hasrequestedroles = false;
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
        // Saving all the user's roles if he has one of the requested roles.
        if ($hasrequestedroles) {
            $user->roles = $userroles;
        // User doesn't have any of the requested roles.
        } else {
            $user->roles = array();
        }

        if ($options['useoverwrites']) {
            foreach ($dc_users_overwrite as $field => $value) {
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
 * @param string $roles list of roles
 * @return array converted string $roles.
 */
function dc_resolve_roles($roles) {
    global $dc_rolescache;

    $allroles = get_all_roles();
    // Building roles cache.
    foreach ($allroles as $key => $role) {
        $dc_rolescache[$role->shortname] = $role->id;
    }

    // Returning all roles.
    if ($roles == 'all') {
        $ret = array();
        foreach ($allroles as $key => $role) {
            if ($role->shortname != 'guest' &&
                    $role->shortname != 'frontpage' &&
                    $role->shortname != 'admin')
            $ret[] = $role->shortname;
        }
    } else {
        $ret = explode(',', $roles);
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
    global $dc_custom_column_widths;

    $lastcolumnindex = count($columns)-1;
    $worksheet->set_column(0, $lastcolumnindex, DC_XLS_COLUMN_WIDTH);
    foreach ($columns as $no => $name) {
        if (isset($dc_custom_column_widths[$name])) {
            $worksheet->set_column($no, $no, $dc_custom_column_widths[$name]);
        }
    }
}
