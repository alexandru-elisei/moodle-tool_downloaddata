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

require_once($CFG->libdir . '/excellib.class.php');
require_once($CFG->libdir . '/coursecatlib.php');
require_once(__DIR__ . '/config.php');

/**
 * TOOL_DOWNLOADDATA_DATA_COURSES - download courses.
 */
define('TOOL_DOWNLOADDATA_DATA_COURSES', 0);

/**
 * TOOL_DOWNLOADDATA_DATA_USERS - download users.
 */
define('TOOL_DOWNLOADDATA_DATA_USERS', 1);

/**
 * TOOL_DOWNLOADDATA_FORMAT_CSV - use csv format for downloaded data.
 */
define('TOOL_DOWNLOADDATA_FORMAT_CSV', 0);

/**
 * TOOL_DOWNLOADDATA_FORMAT_XLS - use Excel 2007 (xls) format for downloaded data.
 */
define('TOOL_DOWNLOADDATA_FORMAT_XLS', 1);

/**
 * TOOL_DOWNLOADDATA_INVALID_ROLES - non-existent user roles requested.
 */
define('TOOL_DOWNLOADDATA_INVALID_ROLES', 1);

// Cache for roles.
$TOOL_DOWNLOADDATA_ROLESCACHE = array();

/**
 * Save requested data to a file in the Excel format. Right now, Moodle only
 * supports Excel2007 format.
 *
 * @param constant $data the type of data to be saved.
 * @param string $output the location of the output file.
 * @param string[] $options save options.
 * @param stdClass $contents the file contents.
 * @param string[] $roles user roles.
 * @return MoodleExcelWorkbook
 */
function tool_downloaddata_save_to_excel($data, $output, $options, $contents, $roles = null) {
    global $DB;
    global $TOOL_DOWNLOADDATA_ROLESCACHE;

    $workbook = new MoodleExcelWorkbook($output);
    if ($data == TOOL_DOWNLOADDATA_DATA_COURSES) {
        $worksheet = tool_downloaddata_config::$worksheetnames['courses'];
        $workbook->$worksheet = $workbook->add_worksheet($worksheet);

        $columns = tool_downloaddata_config::$coursefields;
		if ($options['useoverwrites']) {
			foreach (tool_downloaddata_config::$courseoverwrites as $field => $value) {
				if (!array_search($field, $columns)) {
					$columns[] = $field;
				}
			}
		}
        tool_downloaddata_print_column_names($columns, $workbook->$worksheet);
        tool_downloaddata_set_column_widths($columns, $workbook->$worksheet);

        $row = 1;
        // Saving courses
        foreach ($contents as $key => $course) {
            foreach ($columns as $column => $field) {
                $workbook->$worksheet->write($row, $column, $course->$field);
            }
            $row++;
        }

    } else if ($data == TOOL_DOWNLOADDATA_DATA_USERS) {
        $sheetname = tool_downloaddata_config::$worksheetnames['users'];
        $workbook->$sheetname = $workbook->add_worksheet($sheetname);
		$userfields = tool_downloaddata_config::$userfields;

        $row = 1;
        $maxcolumncount = 0;
		if ($options['useoverwrites']) {
			foreach (tool_downloaddata_config::$useroverwrites as $field => $value) {
				if (!array_search($field, $userfields)) {
					$userfields[] = $field;
				}
			}
		}

        foreach ($contents as $key => $user) {
            // Print user info only if their role was requested.
			if (!empty($user->roles)) {
                $column = 0;
                foreach ($userfields as $key => $field) {
                    $workbook->$sheetname->write($row, $column, $user->$field);
                    $column++;
                }

                foreach ($user->roles as $key => $rolearray) {
                    foreach ($rolearray as $role => $course) {
                        $workbook->$sheetname->write($row, $column, $course);
                        $column++;
                        $workbook->$sheetname->write($row, $column, $role);
                        $column++;
                    }
                }

                $row++;
                if ($maxcolumncount < $column) {
                    $maxcolumncount = $column;
                }
            }
        }

        // Creating the role1, role2, etc. and associated course fields.
        $columncount = count($userfields);
        if ($maxcolumncount > $columncount) {
            $rolenumber = 1;
            for ($i = $columncount; $i < $maxcolumncount; $i += 2) {
                $coursecolumn = 'course' . $rolenumber;
                $columns[] = $coursecolumn;
                $rolecolumn = 'role' . $rolenumber;
                $columns[] = $rolecolumn;
                $rolenumber++;
            }
        }
        tool_downloaddata_print_column_names($columns, $workbook->$sheetname);
        tool_downloaddata_set_column_widths($columns, $workbook->$sheetname);
    }

    return $workbook;
}

/**
 * Save requested data to a comma separated values (CSV) file.
 *
 * @param constant $data the type of data to be saved.
 * @param string $output the location of the output file.
 * @param string[] $options save options.
 * @param stdClass $contents the file contents.
 * @param string[] $roles user roles.
 * @return class csv_export_writer.
 */
function tool_downloaddata_save_to_csv($data, $output, $options, $contents, $roles = null) {
    global $DB;

    $csv = new csv_export_writer($options['delimiter']);
    $csv->set_filename($output);
    if ($data == TOOL_DOWNLOADDATA_DATA_COURSES) {
        // Saving field names
        $fields = tool_downloaddata_config::$coursefields;
        if ($options['useoverwrites']) {
            foreach (tool_downloaddata_config::$courseoverwrites as $field => $value) {
				if (!array_search($field, $fields)) {
					$fields[] = $field;
				}
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
    } else if ($data == TOOL_DOWNLOADDATA_DATA_USERS) {
        $maxrolesnumber = 0;
        foreach ($contents as $key => $user) {
            $rolesnumber = count($user->roles);
            if ($rolesnumber > $maxrolesnumber) {
                $maxrolesnumber = $rolesnumber;
            }
        }

        // Saving field names
        $userfields = tool_downloaddata_config::$userfields;
		if ($options['useoverwrites']) {
			foreach (tool_downloaddata_config::$useroverwrites as $field => $value) {
				if (!array_search($field, $userfields)) {
					$userfields[] = $field;
				}
			}
		}
		$row = $userfields;
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
                foreach ($userfields as $key => $field) {
                    $row[] = $user->$field;
                }
                foreach ($user->roles as $key => $rolesarray) {
					foreach ($rolesarray as $role => $course) {
						$row[] = $course;
						$row[] = $role;
					}
                }
				
                // Adding blank columns until we have the same number of columns.
                $no = count($row);
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
 * @param string[] $options function options.
 * @return stdClass[] the courses.
 */
function tool_downloaddata_get_courses($options = null) {
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
        $course->category_path = tool_downloaddata_resolve_category_path($course->category);
        // Formating startdate to the ISO8601 format.
        $course->startdate = userdate($course->startdate, '%Y-%m-%d');
        // Adding overwrite fields and values.
        if ($options['useoverwrites']) {
            foreach (tool_downloaddata_config::$courseoverwrites as $field => $value) {
                $course->$field = $value;
            }
        }
    }

    if (isset($options['sortbycategorypath']) && $options['sortbycategorypath']) {
        usort($courses, "sort_by_category_alphabetically");
    }

    return $courses;
}

/**
 * Returns all the users to be saved to file.
 *
 * @throws coding_exception.
 * @param string[] $roles the requested roles.
 * @param string[] $options function options.
 * @return stdClass[] the users.
 */
function tool_downloaddata_get_users($roles, $options = null) {
    global $DB;
    global $TOOL_DOWNLOADDATA_ROLESCACHE;

	// Exception if roles haven't been prepared beforehand.
	if (empty($TOOL_DOWNLOADDATA_ROLESCACHE)) {
        throw new coding_exception("Cannot proceed, roles haven't been resolved.");
	}

	// Constructing the requested user fields.
    $userfields = tool_downloaddata_config::$userfields;
	foreach ($userfields as $key => $field) {
		$field = 'u.' . $field;
	}
	$userfields = implode(',', $userfields);

    $courses = tool_downloaddata_get_courses($options);
    $users = array();
	// Finding the users assigned to the course with the specified roles.
    foreach ($courses as $key => $course) {
		$coursecontext = context_course::instance($course->id);
		foreach ($roles as $key => $role) {
			$usersassigned = get_role_users($TOOL_DOWNLOADDATA_ROLESCACHE[$role], $coursecontext, false, $userfields);
			foreach ($usersassigned as $username => $user) {
				if (!isset($users[$username])) {
					$users[$username] = $user;
					$users[$username]->roles = array();
				}
				$users[$username]->roles[] = array($role => $course->shortname);
			}
		}
    }

	// Overwriting fields.
	if (isset($options['useoverwrites']) && $options['useoverwrites']) {
		foreach ($users as $username => $user) {
			foreach (tool_downloaddata_config::$useroverwrites as $field => $value) {
				$user->$field = $value;
			}
		}
	}

    return $users;
}

/**
 * Internal function to sort the courses by category path alphabetically. It
 * will be passed to usort.
 *
 * @param stdClass $a first element to be compared.
 * @param stdClass $b second element to be compared.
 * @return int 0 if equality, 1 if a is higher, -1 otherwise.
 */
function sort_by_category_alphabetically($a, $b) {
    if ($a->category_path == $b->category_path) {
        return 0;
    } else if ($a->category_path > $b->category_path) {
        return 1;
    } else {
        return -1;
    }
}

/**
 * Internal function to resolve category hierarchy.
 *
 * @param int $parentid the parent id.
 * @return string the category hierarchy.
 */
function tool_downloaddata_resolve_category_path($parentid) {
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
 * @param string $roles comma separated list of roles.
 * @return string[] $roles numerically indexed array of roles.
 */
function tool_downloaddata_resolve_roles($roles) {
    global $TOOL_DOWNLOADDATA_ROLESCACHE;

    $allroles = get_all_roles();
    // Building roles cache.
    foreach ($allroles as $key => $role) {
        $TOOL_DOWNLOADDATA_ROLESCACHE[$role->shortname] = $role->id;
    }

    // Returning all roles.
    if ($roles == 'all') {
        $ret = array();
        foreach ($allroles as $key => $role) {
            $isguest = ($role->shortname == 'guest');
            $isfrontpage = ($role->shortname == 'frontpage');
            $isadmin = ($role->shortname == 'admin');
            if (!$isguest && !$isfrontpage && !$isadmin) {
                $ret[] = $role->shortname;
            }
        }
    } else {
        $ret = explode(',', $roles);
        // Checking for invalid roles
        foreach ($ret as $key => $role) {
            if (!isset($TOOL_DOWNLOADDATA_ROLESCACHE[$role])) {
                return TOOL_DOWNLOADDATA_INVALID_ROLES;
            }
        }
    }
    return $ret;
}

/**
 * Print the field names for Excel files.
 *
 * @param string[] $columns column names.
 * @param MoodleExcelWorksheet $worksheet the worksheet.
 */
function tool_downloaddata_print_column_names($columns, $worksheet) {
    $firstrow = 0;
    $column = 0;
    foreach ($columns as $key => $name) {
        $worksheet->write($firstrow, $column, $name);
        $column++;
    }
}

/**
 * Set file column widths for Excel files.
 *
 * @param string[] $columns column names.
 * @param MoodleExcelWorksheet $worksheet the worksheet.
 */
function tool_downloaddata_set_column_widths($columns, $worksheet) {
    $lastcolumnindex = count($columns)-1;
    $worksheet->set_column(0, $lastcolumnindex, tool_downloaddata_config::$columnwidths['default']);
    foreach ($columns as $no => $name) {
        if (isset(tool_downloaddata_config::$columnwidths[$name])) {
            $worksheet->set_column($no, $no, tool_downloaddata_config::$columnwidths[$name]);
        }
    }
}
