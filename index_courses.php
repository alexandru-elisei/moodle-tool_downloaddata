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
require_once(__DIR__ . '/locallib.php');
require_once(__DIR__ . '/config.php');

core_php_time_limit::raise(60 * 60); // 1 hour.
raise_memory_limit(MEMORY_HUGE);

require_login();
admin_externalpage_setup('tooldownloaddata_courses');

$returnurl = new moodle_url('/admin/tool/downloaddata/index_courses.php');

if (!isset($SESSION->customdata)) {
    $SESSION->customdata = array();
    // Adding the default course fields to the selected fields.
    $SESSION->customdata['selectedfields'] = tool_downloaddata_config::$coursefields;

}

$mform = new tool_downloaddata_courses_form(null, $SESSION->customdata);

if ($formdata = $mform->get_data()) {
    // Adding all the valid fields.
    if (!empty($formdata->addallfields)) {
        $SESSION->customdata['selectedfields'] = tool_downloaddata_processor::get_valid_course_fields();

    // Removing all the selected fields.
    } else if (!empty($formdata->removeallfields)) {
        $SESSION->customdata['selectedfields'] = array();

    // Adding the selected fields.
    } else if (!empty($formdata->addfieldselection)) {
        if (!empty($formdata->availablefields)) {
            $validfields = tool_downloaddata_processor::get_valid_course_fields();
            foreach ($formdata->availablefields as $fieldindex) {
                $field = $validfields[intval($fieldindex)];
                if (!in_array($field, $SESSION->customdata['selectedfields'])) {
                    $SESSION->customdata['selectedfields'][] = $field;
                }
            }
        }

    // Removing the selected fields.
    } else if (!empty($formdata->removefieldselection)) {
        if (!empty($formdata->selectedfields) && !empty($SESSION->customdata['selectedfields'])) {
            foreach($formdata->selectedfields as $fieldindex) {
                unset($SESSION->customdata['selectedfields'][intval($fieldindex)]);
            }
        }

    // Downloading the courses.
    } else {
        $options = array();
        $options['format'] = $formdata->format;
        $options['data'] = tool_downloaddata_processor::DATA_COURSES;
        $options['encoding'] = $formdata->encoding;
        $options['roles'] = array();
        $options['usedefaults'] = ($formdata->usedefaults == 'true');
        $options['useoverrides'] = ($formdata->useoverrides == 'true');
        $options['sortbycategorypath'] = ($formdata->sortbycategorypath == 'true');
        $options['delimiter'] = $formdata->delimiter_name;

        if (!empty($SESSION->customdata['selectedfields'])) {
            $fields = $SESSION->customdata['selectedfields'];
        } else {
            throw new moodle_exception('emptyfields', 'tool_downloaddata', $returnurl);
        }
        unset($SESSION->customdata);

        if ($options['useoverrides']) {
            if (!empty($formdata->overrides)) {
                $overrides = tool_downloaddata_process_overrides($formdata->overrides);
            } else if ($options['usedefaults']) {
                $overrides = tool_downloaddata_config::$courseoverrides;
            }
        } else {
            $overrides = array();
        }

        if ($options['useoverrides'] && empty($overrides)) {
            throw new moodle_exception('emptyoverrides', 'tool_downloaddata', $returnurl);
        }

        $processor = new tool_downloaddata_processor($options, $fields, $overrides);
        try {
            $processor->prepare();
        } catch (Exception $e) {
            $e->link = $returnurl;
            throw $e;
        }
        $processor->download();
    }

    unset($_POST);
    $mform = new tool_downloaddata_courses_form(null, $SESSION->customdata);
} else {
    // Removing session data on a page refresh.
    unset($SESSION->customdata);
}

echo $OUTPUT->header();
$errors = null;
$mform->display();
echo $OUTPUT->footer();
