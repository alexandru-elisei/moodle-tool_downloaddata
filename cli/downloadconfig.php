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
 * CLI download moodle configuration file.
 *
 * @package    tool_downloadconfig
 * @copyright  2015 Alexandru Elisei
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->libdir . '/coursecatlib.php');
require_once($CFG->libdir . '/csvlib.class.php');
require_once('../locallib.php');

// Now get cli options.
list($options, $unrecognized) = cli_get_params(array(
    'help' => false,
    'format' => 'csv',
    'data' => '',
    'delimiter' => 'comma',
    'encoding' => 'UTF-8',
    'templatecourse' => '',
    'roles' => '',
    'separatesheets' => true,
    'useoverwrites' => false
),
array(
    'h' => 'help',
    'f' => 'format',
    'd' => 'data',
    'l' => 'delimiter',
    'e' => 'encoding',
    't' => 'templatecourse',
    'r' => 'roles'
));

$help =
"\nDownload Moodle configuration file.

Options:
-h, --help                 Print out this help
-f, --format               Format: csv (default) or xls
-d, --data                 Data to download: courses or users
-l, --delimiter            CSV delimiter: colon, semicolon, tab, cfg, comma (default)
-e, --encoding             CSV file encoding: utf8 (default), ... etc
-t, --templatecourse       Add template course to the downloaded data
-r, --roles                Specific roles for users (comma separated)
    --separatesheets       Save the users with each role on separeate worksheets: true (default) or false
    --useoverwrites        Overwrite specific fields from locallib: true or false (default)

Example:
\$php downloadconfig.php --data=courses --format=xls > output.xls

";

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    fputs(STDERR, get_string('cliunknowoption', 'admin', $unrecognized) . "\n");
    die();
}

if ($options['help']) {
    fputs(STDERR, $help);
    die();
}

$dataoptions = array(
    'courses' => DC_DATA_COURSES,
    'users' => DC_DATA_USERS
);
if (!isset($options['data']) || !isset($dataoptions[$options['data']])) {
    fputs(STDERR, get_string('invaliddata', 'tool_downloadconfig'). "\n");
    fputs(STDERR, $help);
    die();
}
$data = $dataoptions[$options['data']];

$formats = array(
    'csv' => DC_FORMAT_CSV,
    'xls' => DC_FORMAT_XLS
);
if (!isset($options['format']) || !isset($formats[$options['format']])) {
    fputs(STDERR, get_string('invalidformat', 'tool_downloadconfig'));
    fputs(STDERR, $help);
    die();
}
$format = $formats[$options['format']];

$encodings = core_text::get_encodings();
if (!isset($encodings[$options['encoding']])) {
    fputs(STDERR, get_string('invalidencoding', 'tool_downloadconfig'));
    fputs(STDERR, $help);
    die();
}

$delimiters = csv_import_reader::get_delimiter_list();
if (empty($options['delimiter']) || !isset($delimiters[$options['delimiter']])) {
    fputs(STDERR, get_string('invaliddelimiter', 'tool_downloadconfig'));
    fputs(STDERR, $help);
    die();
}

$options['separatesheets'] = ($options['separatesheets'] === true ||
            core_text::strtolower($options['separatesheets']) == 'true');
$options['useoverwrites'] = ($options['useoverwrites'] === true ||
            core_text::strtolower($options['useoverwrites']) == 'true');

// Emulate admin session.
cron_setup_user();

$contents = NULL;
$roles = NULL;
if ($data == DC_DATA_COURSES) {
    $contents = dc_get_courses($options);
    if (empty($contents)) {
        fputs(STDERR, get_string('emptycontents', 'tool_downloadconfig') . "\n");
        die();
    }
} else if ($data == DC_DATA_USERS) {
    $roles = dc_resolve_roles($options['roles']);
    if ($roles == DC_INVALID_ROLES) {
        fputs(STDERR, get_string('invalidroles', 'tool_downloadconfig') . "\n");
        die();
    }
    $contents = dc_get_users($roles, $options);
}
//var_dump($contents);
//var_dump($roles);

/*
$roles = get_all_roles();
var_dump($roles);
 */

//die();

$output = "phonyoutput";
//$workbook = dc_save_to_excel($data, $output, $options, $contents, $roles);
//die();

if ($format == DC_FORMAT_XLS) {
    $workbook = dc_save_to_excel($data, $output, $options, $contents, $roles);
    $workbook->close();
} else if ($format == DC_FORMAT_CSV) {
    $csv = dc_save_to_csv($data, $output, $options, $contents, $roles);
    $csv->download_file();
}

/*
$courses = dc_get_data(DC_DATA_COURSES, $options);
foreach ($courses as $key => $course) {
    $userfields = 'u.username, u.firstname, u.lastname, u.email';
    $coursecontext = context_course::instance($course->id);
    $userinfo = get_role_users(5, $coursecontext, false, $userfields);

    var_dump($userinfo);
}
 */

/*
//$users = $DB->get_records('user', null, '', 'username,id');
$users = $DB->get_records('user');
var_dump($users);
foreach($users as $key => $user) {
    if (isset($user->deleted)) {
        context_system::instance();
        delete_user($user);
    }
}
 */

//var_dump($contents);
