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
 * Web interface for downloading users or courses.
 *
 * @package    tool_downloaddata
 * @copyright  2015 Alexandru Elisei
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/csvlib.class.php');
require_once($CFG->libdir . '/filelib.php');

core_php_time_limit::raise(60*60); // 1 hour should be enough
raise_memory_limit(MEMORY_HUGE);

require_login();
admin_externalpage_setup('tooldownloaddata_courses');

if (empty($options)) {
    $mform1 = new tool_downloaddata_courses_form();

    // Downloading data.
    if ($formdata = $mform1->get_data()) {
        $options = array();
        $options['format'] = $formdata->format;
        $options['data'] = tool_downloaddata_processor::DATA_COURSES;
        $options['encoding'] = $formdata->encoding;
        $options['roles'] = array();
        $options['useoverrides'] = ($formdata->useoverrides == 'true');
        if ($options['useoverrides']) {
            $overrides = tool_downloaddata_config::$courseoverrides;
        } else {
            $overrides = array();
        }
        $fields = tool_downloaddata_config::$coursefields;
        $options['sortbycategorypath'] = ($formdata->sortbycategorypath == 'true');
        $options['delimiter'] = $formdata->delimiter_name;

        $processor = new tool_downloaddata_processor($options, $fields, $overrides);
        $processor->prepare();
        $processor->download();

        /*
        $contents = tool_downloaddata_get_courses($options);
        $output = 'courses';
        $roles = null;
        if ($options['format'] == TOOL_DOWNLOADDATA_FORMAT_XLS) {
            $today = date('Ymd') . '_' . date('Hi');
            $output = $output . '_' . $today . '.xls';
            $workbook = tool_downloaddata_save_to_excel($options['data'], $output, $options, $contents, $roles);
            $workbook->close();
        } else if ($options['format'] == TOOL_DOWNLOADDATA_FORMAT_CSV) {
            $csv = tool_downloaddata_save_to_csv($options['data'], $output, $options, $contents, $roles);
            $csv->download_file();
        }
         */
    } else {
        // Printing the form.
        echo $OUTPUT->header();
        $mform1->display();
        echo $OUTPUT->footer();
        die();
    }
} 

die;
