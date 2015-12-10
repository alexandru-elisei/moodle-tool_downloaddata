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
require(__DIR__ . '/locallib.php');

core_php_time_limit::raise(60 * 60); // 1 hour.
raise_memory_limit(MEMORY_HUGE);

require_login();
admin_externalpage_setup('tooldownloaddata_courses');

$returnurl = new moodle_url('/admin/tool/downloaddata/index_courses.php');

if (empty($options)) {
    $mform1 = new tool_downloaddata_courses_form();

    // Downloading data.
    if ($formdata = $mform1->get_data()) {
        $options = array();
        $options['format'] = $formdata->format;
        $options['data'] = tool_downloaddata_processor::DATA_COURSES;
        $options['encoding'] = $formdata->encoding;
        $options['roles'] = array();
        $options['usedefaults'] = ($formdata->usedefaults == 'true');
        $options['useoverrides'] = ($formdata->useoverrides == 'true');
        $options['sortbycategorypath'] = ($formdata->sortbycategorypath == 'true');
        $options['delimiter'] = $formdata->delimiter_name;

        if (!empty($formdata->fields)) {
            $fields = tool_downloaddata_process_fields($formdata->fields);
        } else if ($options['usedefaults']) {
            $fields = tool_downloaddata_config::$coursefields;
        }

        if ($options['useoverrides']) {
            if (!empty($formdata->overrides)) {
                $overrides = tool_downloaddata_process_overrides($formdata->overrides);
            } else if ($options['usedefaults']) {
                $overrides = tool_downloaddata_config::$courseoverrides;
            }
        } else {
            $overrides = array();
        }

        if (empty($fields)) {
            print_error('emptyfields', 'tool_downloaddata', $returnurl);
        }
        if ($options['useoverrides'] && empty($overrides)) {
            print_error('emptyoverrides', 'tool_downloaddata', $returnurl);
        }

        $processor = new tool_downloaddata_processor($options, $fields, $overrides);
        try {
            $processor->prepare();
        } catch (Exception $e) {
            print_error($e->errorcode, $e->module, $returnurl, $e->a);
        }
        $processor->download();
    } else {
        // Printing the form.
        echo $OUTPUT->header();
        $errors = null;
        $mform1->display();
        echo $OUTPUT->footer();
        die();
    }
}

die;
