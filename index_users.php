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

core_php_time_limit::raise(60*60); // 1 hour should be enough
raise_memory_limit(MEMORY_HUGE);

require_login();
admin_externalpage_setup('tooldownloaddata_users');

if (empty($options)) {
    $mform1 = new tool_downloaddata_users_form();
    // Downloading data.
    if ($formdata = $mform1->get_data()) {
        $options = array();
        $options['format'] = $formdata->format;
        $options['data'] = tool_downloaddata_processor::DATA_USERS;
        $options['encoding'] = $formdata->encoding;
        $options['roles'] = $formdata->roles;
        $options['usedefaults'] = ($formdata->usedefaults == 'true');
        $options['useoverrides'] = ($formdata->useoverrides == 'true');
        $options['sortbycategorypath'] = false;
        $options['delimiter'] = $formdata->delimiter_name;

        if (!empty($formdata->fields)) {
            $fields = tool_downloaddata_process_fields($formdata->fields);
        } else if ($options['usedefaults']) {
            $fields = tool_downloaddata_config::$userfields;
        }

        if ($options['useoverrides']) {
            if (!empty($formdata->overrides)) {
                $overrides = tool_downloaddata_process_overrides($formdata->overrides);
            } else if ($options['usedefaults']) {
                $overrides = tool_downloaddata_config::$useroverrides;
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
        $processor->prepare();
        $processor->download();
    } else {
        // Printing the form.
        echo $OUTPUT->header();
        $mform1->display();
        echo $OUTPUT->footer();
        die();
    }
} 

die;
