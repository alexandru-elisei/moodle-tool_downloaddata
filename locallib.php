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
define('DC_DATA_TEACHERS', 1);

define('DC_FORMAT_CSV', 0);
define('DC_FORMAT_XLS', 1);

define('DC_XLS_CATEGORY_PATH_WIDTH', 30);
define('DC_XLS_COLUMN_WIDTH', 15);
define('DC_XLS_COURSES_WORKSHEET_NAME', 'Courses');

/** CSV output file fields. **/
$dc_output_csv_fields = array('shortname', 'fullname', 'category_path');

/** Excel output file fields. **/
$dc_output_xls_fields = $dc_output_csv_fields;

/**
 * Save requested data to a file in the Excel format. Right now, Moodle only 
 * supports Excel2007 format.
 *
 * @param constant $data the type of data to be saved.
 * @param string $output the location of the output file.
 * @param array of stdClass $contents the file contents.
 * @param array $options save options.
 * @return void.
 */
function dc_save_to_excel($data, $output, $contents, $options) {
    global $dc_output_xls_fields;

    $workbook = new MoodleExcelWorkbook($output);

    if ($data == DC_DATA_COURSES) {
        $worksheet = DC_XLS_COURSES_WORKSHEET_NAME;
        $workbook->$worksheet = $workbook->add_worksheet($worksheet);
        $row = 0;
        $column = 0;
        
        // Saving column names
        foreach ($dc_output_xls_fields as $field => $longname) {
            $workbook->$worksheet->write($row, $column, $longname);
            $column++;
        }
        
        // Formatting columns
        $last_column = count($dc_output_xls_fields) - 1;
        if (!empty($options['templatecourse'])) {
            $workbook->$worksheet->write($row, $column, 'templatecourse');
            $last_column++;
        }
        $workbook->$worksheet->set_row($row, NULL, array('h_align' => 'center'));
        $workbook->$worksheet->set_column(0, $last_column, DC_XLS_COLUMN_WIDTH);

        // Saving courses
        foreach($contents as $key => $course) {
            $row++;
            $column = 0;
            foreach ($dc_output_xls_fields as $key => $field) {
                $workbook->$worksheet->write($row, $column, $course->$field);
                if ($field == 'category_path') {
                    $workbook->$worksheet->set_column($column, $column,
                                            DC_XLS_CATEGORY_PATH_WIDTH);
                }
                $column++;
            }
            if (!empty($options['templatecourse'])) {
                $workbook->$worksheet->write($row, $column, 
                                             $options['templatecourse']);
            }
        }
    }
   $workbook->close();
}

/**
 * Save requested data to a comma separated values (CSV) file.
 *
 * @param constant $data the type of data to be saved.
 * @param string $output the location of the output file.
 * @param array of stdClass $contents the file contents.
 * @param array $options save options.
 * @return void.
 */
function dc_save_to_csv($data, $output, $contents, $options) {
    global $dc_output_csv_fields;

    $csv = new csv_export_writer($options['delimiter']);
    if ($data == DC_DATA_COURSES) {
        // Saving field names
        $fields = $dc_output_csv_fields;
        if (!empty($options['templatecourse'])) {
            $fields[] = 'templatecourse';
        }
        $csv->add_data($fields);

        // Saving courses
        foreach ($contents as $key => $course) {
            $row = array();
            foreach ($dc_output_csv_fields as $key => $field) {
                $row[] = $course->$field;
            }
            if (!empty($options['templatecourse'])) {
                $row[] = $options['templatecourse'];
            }
            $csv->add_data($row);
        }
    }
    $csv->download_file();
}

/**
 * Get the data to be saved to a file.
 *
 * @param constant $data the type of data.
 * @return array the information.
 */
function dc_get_data($data) {
    global $DB;

    $ret = array();
    if ($data == DC_DATA_COURSES) {
        $ret = $DB->get_records('course');
        // Ignoring course Moodle
        unset($ret[1]);
        foreach($ret as $key => $course) {
            $course->category_path = dc_resolve_category_path($course->category);
        }
    }

    return $ret;
}

/**
 * Internal function to resolve category hierarchy
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
