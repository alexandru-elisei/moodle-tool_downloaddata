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
 * File containing processor class.
 *
 * @package    tool_downloaddata
 * @copyright  2015 Alexandru Elisei
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/moodlelib.php');
require_once($CFG->libdir . '/excellib.class.php');
require_once($CFG->libdir . '/coursecatlib.php');
require_once($CFG->libdir . '/csvlib.class.php');
require_once(__DIR__ . '/../config.php');

/**
 * Processor class.
 *
 * @package    tool_downloaddata
 * @copyright  2015 Alexandru Elisei
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool_downloaddata_processor {
    /**
     * Download courses.
     */
    const DATA_COURSES = 0;

    /**
     * Download users.
     */
    const DATA_USERS = 1;

    /**
     * Use csv format for downloaded data.
     */
    const FORMAT_CSV = 0;

    /**
     * Use Excel 2007 (xls) format for downloaded data.
     */
    const FORMAT_XLS = 1;

    /** @var int Download courses or users. */
    protected $coursesorusers;

    /** @var stdClass[] Content to download. */
    protected $contents;

    /** @var int Download data format. */
    protected $format = self::FORMAT_CSV;

    /** @var int Delimiter for csv format. */
    protected $delimiter = 'comma';

    /** @var string Encoding. */
    protected $encoding = 'UTF-8';

    /** @var bool Whether the process has been started or not. */
    protected $processstarted = false;

    /** @var string Download only users that have these roles. */
    protected $requestedroles = 'all';

    /** @var string[] Fields to download. */
    protected $fields;

    /** @var string[] Valid course fields. */
    protected static $validcoursefields = array( 'shortname', 'fullname', 'idnumber',
        'category', 'category_idnumber', 'category_path', 'visible',
        'startdate', 'summary', 'format', 'theme', 'lang', 'newsitems',
        'showgrades', 'showreports', 'legacyfiles', 'maxbytes', 'groupmode',
        'groupmodeforce', 'enablecompletion'
    );

    /** @var string[] Standard user fields. */
    protected static $standarduserfields = array('id', 'username', 'email', 'city',
        'country', 'lang', 'timezone', 'mailformat', 'maildisplay',
        'maildigest', 'autosubscribe', 'institution',
        'department', 'idnumber', 'skype', 'msn', 'aim', 'yahoo', 'icq',
        'phone1', 'phone2', 'address', 'url', 'description',
        'descriptionformat', 'auth'
    );

    /** @var string[] Fields to be overridden. */
    protected $overrides;

    /** @var string[] Resolved roles. */
    protected $resolvedroles = array();

    /** @var bool Whether fields should be overridden or not. */
    protected $useoverrides = false;

    /** @var bool Sort courses by category path. */
    protected $sortbycategorypath = false;

    /** @var string[] Cache for roles. */
    protected $rolescache = null;

    /** @var csv_export_writer | MoodleExcelWorkbook File object with the requested data. */
    protected $fileobject = null;

    /**
     * Class constructor.
     *
     * @throws moodle_exception.
     * @param string[] $options Download options.
     * @param string[] $fields The fields that will be downloaded.
     * @param string[] $overrides Fields to be overridden.
     */
    public function __construct($options, $fields, $overrides = null) {
        if (!isset($options['data']) ||
                !in_array($options['data'], array(self::DATA_COURSES, self::DATA_USERS))) {
            throw new moodle_exception('invaliddata', 'tool_downloaddata');
        }
        $this->coursesorusers = (int)$options['data'];

        $this->fields = $fields;

        if (isset($options['format'])) {
            if (!in_array($options['format'], array(self::FORMAT_CSV, self::FORMAT_XLS))) {
                throw new moodle_exception('invalidformat', 'tool_downloaddata');
            }
            $this->format = (int)$options['format'];
        }

        if ($this->format == self::FORMAT_CSV && isset($options['delimiter'])) {
            $delimiters = csv_import_reader::get_delimiter_list();
            if (!isset($delimiters[$options['delimiter']])) {
                throw new moodle_exception('invaliddelimiter', 'tool_downloaddata');
            }
            $this->delimiter = $options['delimiter'];
        }

        if (isset($options['encoding'])) {
            $encodings = core_text::get_encodings();
            if (!isset($encodings[$options['encoding']])) {
                throw new moodle_exception('invalidencoding', 'tool_uploadcourse');
            }
            $this->encoding = $options['encoding'];
        }

        if (isset($options['roles'])) {
            $this->requestedroles = $options['roles'];
        }

        if (isset($options['useoverrides']) && $options['useoverrides'] == true) {
            $this->useoverrides = $options['useoverrides'];
            if (empty($overrides)) {
                throw new moodle_exception('emptyoverrides', 'tool_downloaddata');
            }
            $this->overrides = $overrides;
        }

        if (isset($options['sortbycategorypath'])) {
            $this->sortbycategorypath = $options['sortbycategorypath'];
        }

    }

    /**
     * Prepare the file to be downloaded.
     *
     * @throws coding_exception | moodle_exception.
     */
    public function prepare() {
        global $DB;

        if ($this->processstarted) {
            throw new coding_exception(get_string('processstarted', 'tool_downloaddata'));
        }
        $this->processstarted = true;

        if ($this->coursesorusers === self::DATA_COURSES) {
            $validationresult = $this->validate_course_fields();
            if ($validationresult !== true) {
                throw new moodle_exception('invalidfield', 'tool_downloaddata', '', $validationresult);
            }
            $this->contents = $this->get_courses();
            if ($this->format == self::FORMAT_CSV) {
                $this->fileobject = $this->save_courses_to_csv();
            } else if ($this->format == self::FORMAT_XLS) {
                $this->fileobject = $this->save_courses_to_xls();
            }

        } else if ($this->coursesorusers === self::DATA_USERS) {
            $validationresult = $this->validate_user_fields();
            if ($validationresult !== true) {
                throw new moodle_exception('invalidfield', 'tool_downloaddata', '', $validationresult);
            }

            $this->resolvedroles = $this->resolve_roles();
            $this->contents = $this->get_users();
            if ($this->format === self::FORMAT_CSV) {
                $this->fileobject = $this->save_users_to_csv();
            } else if ($this->format === self::FORMAT_XLS) {
                $this->fileobject = $this->save_users_to_xls();
            }
        }
    }

    /**
     * Download the file object.
     *
     * @throws coding_exception.
     */
    public function download() {
        if (is_null($this->fileobject)) {
            throw new coding_exception(get_string('filenotprepared', 'tool_downloaddata'));
        }
        if ($this->format === self::FORMAT_CSV) {
            $this->fileobject->download_file();
        } else if ($this->format === self::FORMAT_XLS) {
            $this->fileobject->close();
        }
    }

    /**
     * Return the file object with the requested data.
     *
     * @throws coding_exception.
     * @return csv_export_writer | MoodleExcelWorkbook The file object.
     */
    public function get_file_object() {
        if (is_null($this->fileobject)) {
            throw new coding_exception(get_string('filenotprepared', 'tool_downloaddata'));
        }
        return $this->fileobject;
    }

    /**
     * Get the courses to be saved to a file. The courses are returned with all
     * the available fields.
     *
     * @return stdClass[] The courses.
     */
    protected function get_courses() {
        global $DB;

        $courses = get_courses();
        // Ignoring course Moodle.
        foreach ($courses as $key => $course) {
            if (core_text::strtolower($course->shortname) == 'moodle') {
                unset($courses[$key]);
                break;
            }
        }
        foreach ($courses as $key => $course) {
            $course->category_path = $this->resolve_category_path($course->category);
            // Formating startdate to the ISO8601 format.
            $course->startdate = userdate($course->startdate, '%Y-%m-%d');
            // Adding override fields and values.
            if ($this->useoverrides) {
                foreach ($this->overrides as $field => $value) {
                    $course->$field = $value;
                }
            }
        }

        if ($this->sortbycategorypath) {
            usort($courses, function($a, $b) {
                                if ($a->category_path > $b->category_path) {
                                    return 1;
                                } else if ($a->category_path < $b->category_path) {
                                    return -1;
                                } else {
                                    return 0;
                                }
            });
        }

        return $courses;
    }

    /**
     * Resolve category hierarchy.
     *
     * @param int $parentid The parent id.
     * @return string The category hierarchy.
     */
    protected function resolve_category_path($parentid) {
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
     * Save requested courses to a comma separated values (CSV) file.
     *
     * @return csv_export_writer The csv file object.
     */
    protected function save_courses_to_csv() {
        global $DB;

        $csv = new csv_export_writer($this->delimiter);
        $csv->set_filename('courses');

        // Saving field names.
        $fields = $this->fields;
        if ($this->useoverrides) {
            foreach ($this->overrides as $field => $value) {
                if (!array_search($field, $fields)) {
                    $fields[] = $field;
                }
            }
        }
        $csv->add_data($fields);

        // Saving courses.
        foreach ($this->contents as $key => $course) {
            $row = array();
            foreach ($fields as $key => $field) {
                $row[] = $course->$field;
            }
            $csv->add_data($row);
        }
        return $csv;
    }

    /**
     * Save requested users to a comma separated values (CSV) file.
     *
     * @return csv_export_writer The csv file object.
     */
    protected function save_users_to_csv() {
        global $DB;

        $csv = new csv_export_writer($this->delimiter);
        $csv->set_filename('users');
        $maxrolesnumber = 0;
        // Getting the maximum number of roles a user can have.
        foreach ($this->contents as $key => $user) {
            $rolesnumber = count($user->roles);
            if ($rolesnumber > $maxrolesnumber) {
                $maxrolesnumber = $rolesnumber;
            }
        }

        // Saving field names.
        $userfields = $this->fields;
        if ($this->useoverrides) {
            foreach ($this->overrides as $field => $value) {
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

        foreach ($this->contents as $key => $user) {
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

        return $csv;
    }

    /**
     * Save requested data to a file in the Excel format. Right now, Moodle only
     * supports Excel2007 format.
     *
     * @return MoodleExcelWorkbook The file object.
     */
    protected function save_courses_to_xls() {
        global $DB;

        $filename = 'courses';
        $filename .= clean_filename('-' . gmdate("Ymd_Hi"));
        $workbook = new MoodleExcelWorkbook($filename);
        $worksheet = tool_downloaddata_config::$worksheetnames['courses'];
        $workbook->$worksheet = $workbook->add_worksheet($worksheet);

        $columns = $this->fields;
        if ($this->useoverrides) {
            foreach ($this->overrides as $field => $value) {
                if (!array_search($field, $columns)) {
                    $columns[] = $field;
                }
            }
        }
        $this->print_column_names($columns, $workbook->$worksheet);
        $this->set_column_widths($columns, $workbook->$worksheet);

        $row = 1;
        // Saving courses.
        foreach ($this->contents as $key => $course) {
            foreach ($columns as $column => $field) {
                $workbook->$worksheet->write($row, $column, $course->$field);
            }
            $row++;
        }

        return $workbook;
    }

    /**
     * Get all the users to be saved to file.
     *
     * @return stdClass[] The users.
     */
    protected function get_users() {
        global $DB;

        // Constructing the requested user fields.
        $userfields = array();
        foreach ($this->fields as $field) {
            $userfields[] = 'u.' . $field;
        }
        $userfields = implode(',', $userfields);

        $courses = $this->get_courses();
        $users = array();

        // Finding the users assigned to the course with the specified roles.
        foreach ($courses as $key => $course) {
            $coursecontext = context_course::instance($course->id);
            foreach ($this->resolvedroles as $key => $role) {
                $usersassigned = get_role_users($this->rolescache[$role], $coursecontext, false, $userfields, $userfields);
                foreach ($usersassigned as $username => $user) {
                    if (!isset($users[$username])) {
                        $users[$username] = $user;
                        $users[$username]->roles = array();
                    }
                    $users[$username]->roles[] = array($role => $course->shortname);
                }
            }
        }

        // Overridding fields.
        if ($this->useoverrides) {
            foreach ($users as $username => $user) {
                foreach ($this->overrides as $field => $value) {
                    $user->$field = $value;
                }
            }
        }

        return $users;
    }

    /**
     * Validate and process specified user roles.
     *
     * @throws moodle_exception.
     * @return string[] Numerically indexed array of roles.
     */
    protected function resolve_roles() {
        $this->rolescache = array();
        $allroles = get_all_roles();

        // Building the roles cache.
        foreach ($allroles as $key => $role) {
            $this->rolescache[$role->shortname] = $role->id;
        }

        // Returning all roles.
        if ($this->requestedroles == 'all') {
            $ret = self::get_all_valid_roles();
        } else {
            $ret = explode(',', $this->requestedroles);
            // Checking for invalid roles.
            foreach ($ret as $key => $role) {
                if (!isset($this->rolescache[$role])) {
                    throw new moodle_exception('invalidrole', 'tool_downloaddata', '', $role);
                }
            }
        }
        return $ret;
    }

    /**
     * Save users in the Excel 2007 (xls) format.
     *
     * @return MoodleExcelWorkbook
     */
    public function save_users_to_xls() {
        global $DB;

        $filename = 'users';
        $filename .= clean_filename('-' . gmdate("Ymd_Hi"));
        $workbook = new MoodleExcelWorkbook($filename);
        $sheetname = tool_downloaddata_config::$worksheetnames['users'];
        $workbook->$sheetname = $workbook->add_worksheet($sheetname);

        $userfields = $this->fields;
        $row = 1;
        $maxcolumncount = 0;
        if ($this->useoverrides) {
            foreach ($this->overrides as $field => $value) {
                if (!array_search($field, $userfields)) {
                    $userfields[] = $field;
                }
            }
        }

        foreach ($this->contents as $key => $user) {
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

        // Creating the role1, role2, etc. and associated course1, course2, etc. fields.
        $columncount = count($userfields);
        $columns = $userfields;
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
        $this->print_column_names($columns, $workbook->$sheetname);
        $this->set_column_widths($columns, $workbook->$sheetname);

        return $workbook;
    }

    /**
     * Print the field names for Excel files.
     *
     * @param string[] $columns Column names.
     * @param MoodleExcelWorksheet $worksheet The worksheet.
     */
    protected function print_column_names($columns, $worksheet) {
        $column = 0;
        foreach ($columns as $key => $name) {
            $worksheet->write(0, $column, $name);
            $column++;
        }
    }

    /**
     * Set file column widths for Excel files.
     *
     * @param string[] $columns Column names.
     * @param MoodleExcelWorksheet $worksheet The worksheet.
     */
    protected function set_column_widths($columns, $worksheet) {
        $lastcolumnindex = count($columns) - 1;
        $worksheet->set_column(0, $lastcolumnindex, tool_downloaddata_config::$columnwidths['default']);
        foreach ($columns as $no => $name) {
            if (isset(tool_downloaddata_config::$columnwidths[$name])) {
                $worksheet->set_column($no, $no, tool_downloaddata_config::$columnwidths[$name]);
            }
        }
    }

    /**
     * Validates fields requested for courses.
     *
     * @return bool|string True if validation passes, the invalid field otherwise
     */
    protected function validate_course_fields() {
        $validcoursefields = self::get_valid_course_fields();
        foreach ($this->fields as $field) {
            if (!in_array($field, $validcoursefields, true)) {
                return $field;
            }
        }

        return true;
    }

    /**
     * Validates fields requested for users.
     *
     * @return bool|string True if validation passes, the invalid field otherwise
     */
    protected function validate_user_fields() {
        $validuserfields = self::get_valid_user_fields();
        $profilefields = self::get_profile_fields();
        $processed = array();
        foreach ($this->fields as $key => $field) {
            $lcfield = core_text::strtolower($field);
            $processedfield = null;

            if (in_array($field, $validuserfields) ||
                    in_array($lcfield, $validuserfields)) {
                // Standard fields are only lowercase.
                $processedfield = $lcfield;

            } else if (in_array($field, $profilefields)) {
                // Exact profile field name match - these are case sensitive.
                $processedfield = $field;

            } else if (in_array($lcfield, $profilefields)) {
                // Hack: somebody wrote uppercase, but the system knows only lowercase profile field.
                $processedfield = $lcfield;
            }

            // If the field hasn't been processed it means it's an invalid field.
            if (is_null($processedfield)) {
                return $field;
            } else {
                $processed[$key] = $processedfield;
            }
        }

        $this->fields = $processed;

        return true;
    }

    /**
     * Get the valid course fields.
     *
     * @return string[] Numerically indexed array of course fields.
     */
    public static function get_valid_course_fields() {
        return self::$validcoursefields;
    }

    /**
     * Get the valid user fields.
     *
     * @return string[] Numerically indexed array of user fields.
     */
    public static function get_valid_user_fields() {
        $otherfields = get_all_user_name_fields();
        // get_all_user_name_fields() returns a dictionary, not a numerically indexed array.
        $otherfields = array_values($otherfields);
        $fields = array_merge(self::$standarduserfields, $otherfields);
        return $fields;
    }

    /**
     * Get the user profile fields.
     *
     * @return string[] Numerically indexed array of user profile fields.
     */
    public static function get_profile_fields() {
        global $DB;
        $fields = array();
        if ($proffields = $DB->get_records('user_info_field')) {
            foreach ($proffields as $key => $proffield) {
                $profilefieldname = 'profile_field_'.$proffield->shortname;
                $ret[] = $profilefieldname;
            }
        }

        return $fields;
    }

    /**
     * Get a list of all valid roles that can be requested.
     *
     * @return string[] Numerically indexed array of all valid roles.
     */
    public static function get_all_valid_roles() {
        $allroles = get_all_roles();
        $roles = array();
        foreach ($allroles as $key => $role) {
            // Ignoring system roles.
            $isguest = ($role->shortname == 'guest');
            $isfrontpage = ($role->shortname == 'frontpage');
            $isadmin = ($role->shortname == 'admin');
            if (!$isguest && !$isfrontpage && !$isadmin) {
                $roles[] = $role->shortname;
            }
        }

        return $roles;
    }
}
