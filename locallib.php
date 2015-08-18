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

define('DC_INPUT_COURSES', 0);
define('DC_INPUT_TEACHERS', 1);

define('DC_FORMAT_CSV', 0);
define('DC_FORMAT_XLS', 1);

/**
 * Save requested data to a file in the Excel2007 format.
 *
 * @param constant $input the type of data to be saved.
 * @param string $output the location of the output file.
 * @param stdClass $contents the file contents.
 * @param array $options save options.
 * @return void.
 */
function dc_save_excel($input, $output, $contents, $options) {
    $workbook = new MoodleExcelWorkbook($output);

    /*
    print "Workbook (before worksheet):\n";
    var_dump($workbook);
     */

    $workbook->add_worksheet($input);

    /*
    print "Workbook (after worksheet):\n";
    var_dump($workbook);
     */

   // if ($input == DC_INPUT_COURSES) {
}

/**
 * Get the data to be saved to a file.
 *
 * @param constant $input the type of data.
 * @return array the information.
 */
function dc_get_data($input) {
    global $DB;

    if ($input == DC_INPUT_COURSES) {
        $courses = $DB->get_records('course');
        $categories = $DB->get_records('course_categories');

        //var_dump($courses);
        //print "Categories:\n";
        //var_dump($categories);
    }
}
