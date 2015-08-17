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
 * Download site configuration in a CSV file.
 *
 * @package    tool
 * @subpackage downloadconfig
 * @copyright  2015 Alexandru Elisei
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir.'/csvlib.class.php');
require_once($CFG->dirroot.'/course/lib.php');
require_once($CFG->libdir . '/filelib.php');
//require_once('locallib.php');
require_once('downloadconfig_form.php');

$iid         = optional_param('iid', '', PARAM_INT);
//$previewrows = optional_param('previewrows', 10, PARAM_INT);

//@set_time_limit(60*60); // 1 hour should be enough
//raise_memory_limit(MEMORY_HUGE);

require_login();
admin_externalpage_setup('tooldownloadconfig');
require_capability('moodle/course:create', context_system::instance());

$returnurl = new moodle_url('/admin/tool/downloadconfig/index.php');
$bulknurl  = new moodle_url('/admin/tool/downloadconfig/index.php');

//$today = time();
//$today = make_timestamp(date('Y', $today), date('m', $today), date('d', $today), 0, 0, 0);


$mform1 = new tool_downloadconfig_form();

/// Print the form

echo $OUTPUT->header();
//echo $OUTPUT->heading_with_help(get_string('donwloadconfig', 'tool_downloadconfig'), 'downloadconfig', 'tool_downloadconfig');
$mform1->display();
echo $OUTPUT->footer();
die;
