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

require('../../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir.'/csvlib.class.php');
require_once($CFG->dirroot.'/course/lib.php');
require_once($CFG->libdir . '/filelib.php');
require_once('locallib.php');
require_once('index_form.php');

core_php_time_limit::raise(60*60); // 1 hour should be enough
raise_memory_limit(MEMORY_HUGE);

require_login();
admin_externalpage_setup('tooldownloaddata');
require_capability('moodle/course:create', context_system::instance());
require_capability('moodle/user:create', context_system::instance());

if (empty($options)) {
    $mform1 = new tool_index_form();

    // Downloading data.
    if ($formdata = $mform1->get_data()) {
        $options = array();
        $options['format'] = $formdata->format;
        $options['data'] = $formdata->data;
        $options['encoding'] = $formdata->encoding;
        $options['roles'] = $formdata->roles;
        $options['separatesheets'] = true;
        $options['useoverwrites'] = ($formdata->useoverwrites == 'true');
        $options['sortbycategorypath'] = ($formdata->sortbycategorypath == 'true');
        $options['delimiter'] = $formdata->delimiter_name;

        $contents = null;
        $roles = null;
        if ($options['data'] == ADMIN_TOOL_DOWNLOADDATA_DATA_COURSES) {
            $contents = dd_get_courses($options);
            $output = 'courses';
        } else if ($options['data'] == ADMIN_TOOL_DOWNLOADDATA_DATA_USERS) {
            $roles = dd_resolve_roles($options['roles']);
            $contents = dd_get_users($roles, $options);
            $output = $options['roles'];
        }

        if ($options['format'] == ADMIN_TOOL_DOWNLOADDATA_FORMAT_XLS) {
            $today = date('Ymd') . '_' . date('Hi');
            $output = $output . '_' . $today . '.xls';
            $workbook = dd_save_to_excel($options['data'], $output, $options, $contents, $roles);
            $workbook->close();
        } else if ($options['format'] == ADMIN_TOOL_DOWNLOADDATA_FORMAT_CSV) {
            $csv = dd_save_to_csv($options['data'], $output, $options, $contents, $roles);
            $csv->download_file();
        }
    } else {
        // Printing the form.
        echo $OUTPUT->header();
        $mform1->display();
        echo $OUTPUT->footer();
        die();
    }
} 

die;
