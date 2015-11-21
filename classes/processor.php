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

    /** @var int download courses or users. */
    protected $coursesorusers;

    /** @var stdClass[] content to download. */
    protected $contents;

    /** @var int download data format. */
    protected $format = self::FORMAT_CSV;

    /** @var int delimiter for csv format. */
    protected $delimiter = 'comma';

    /** @var string encoding. */
    protected $encoding = 'UTF-8';

    /** @var bool whether the process has been started or not. */
    protected $processstarted = false;

    /** @var string download only users that have these roles. */
    protected $roles = 'all';

    /** @var string[] resolved roles. */
    protected $resolvedroles = array();

    /** var bool overwrite fields. */
    protected $useoverwrites = false;

    /** var bool sort courses by category path. */
    protected $sortbycategorypath = false;

    /** @var string[] cache for roles. */
    protected $rolescache = array(); 

    public function __construct($options) {

        if (!isset($options['data']) ||
                !in_array($options['data'], array(self::DATA_COURSES, self::DATA_USERS))) {
            throw new coding_exception(get_string('invaliddata', 'tool_downloaddata'));
        }
        $this->coursesorusers = (int) $options['data'];

        if (isset($options['format'])) {
            if (!in_array($options['format'], array(self::FORMAT_CSV, self::FORMAT_XLS))) {
                throw new coding_exception(get_string('invalidformat', 'tool_downloaddata'));
            }
            $this->format = $options['format'];
        }

        if ($this->format == self::FORMAT_CSV && isset($options['delimiter'])) {
            $delimiters = csv_import_reader::get_delimiter_list();
            if (!isset($delimiters[$options['delimiter']])) {
                throw new coding_exception(get_string('invaliddelimiter', 'tool_downloaddata'));
            }
            $this->delimiter = $options['delimiter'];
        }

        if (isset($options['encoding'])) {
            $encodings = core_text::get_encodings();
            if (!isset($encodings[$options['encoding']])) {
                throw new coding_exception(get_string('invalidencoding', 'tool_uploadcourse'));
            }
            $this->encoding = $options['encoding'];
        }

        if (isset($options['roles'])) {
            $this->roles = $options['roles'];
        }

        if (isset($options['useoverwrites'])) {
            $this->useoverwrites = $options['useoverwrites'];
        }

        if (isset($options['sortbycategorypath'])) {
            $this->sortbycategorypath = $options['sortbycategorypath'];
        }
    }

    public function execute() {
        if ($this->processstarted) {
            throw new coding_exception(get_string('processstarted', 'tool_downloaddata'));
        }
        $this->processstarted = true;

        if ($this->coursesorusers === self::DATA_COURSES) {
            $this->contents = $this->get_courses();
            if ($this->format === self::FORMAT_CSV) {
                $csv = $this->save_courses_to_csv();
                return $csv->download_file();
            }
        }
    }


    /**
     * Get the courses to be saved to a file.
     *
     * @param string[] $options function options.
     * @return stdClass[] the courses.
     */
    protected function get_courses() {
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
            $course->category_path = $this->resolve_category_path($course->category);
            // Formating startdate to the ISO8601 format.
            $course->startdate = userdate($course->startdate, '%Y-%m-%d');
            // Adding overwrite fields and values.
            if ($this->useoverwrites) {
                foreach (tool_downloaddata_config::$courseoverwrites as $field => $value) {
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
     * @param int $parentid the parent id.
     * @return string the category hierarchy.
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
     * @return class csv_export_writer.
     */
    function save_courses_to_csv() {
        global $DB;

        $csv = new csv_export_writer($this->delimiter);
        $csv->set_filename('courses');
        
        // Saving field names
        $fields = tool_downloaddata_config::$coursefields;
        if ($this->useoverwrites) {
            foreach (tool_downloaddata_config::$courseoverwrites as $field => $value) {
                if (!array_search($field, $fields)) {
                    $fields[] = $field;
                }
            }
        }
        $csv->add_data($fields);

        // Saving courses
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
     * @return class csv_export_writer.
     */
    function save_users_to_csv() {
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

        // Saving field names
        $userfields = tool_downloaddata_config::$userfields;
        if ($this->useoverwrites) {
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
}
