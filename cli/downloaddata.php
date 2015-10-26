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
 * CLI download moodle data file.
 *
 * @package    tool_downloaddata
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
    'roles' => 'all',
    'separatesheets' => true,
    'useoverwrites' => false,
    'sortbycategorypath' => true
),
array(
    'h' => 'help',
    'f' => 'format',
    'd' => 'data',
    'l' => 'delimiter',
    'e' => 'encoding',
    'r' => 'roles',
    's' => 'sortbycategorypath'
));

$help =
"\nDownload Moodle data file.

Options:
-h, --help                 Print out this help
-f, --format               Format: csv (default) or xls
-d, --data                 Data to download: courses or users
-l, --delimiter            CSV delimiter: colon, semicolon, tab, cfg, comma (default)
-e, --encoding             CSV file encoding: utf8 (default), ... etc
-r, --roles                Specific roles for users (comma separated) or all roles
-s, --sortbycategorypath   Sort courses by category path alphabetically: true (default) or false
    --separatesheets       Separate worksheets for roles: true (default) or false
    --useoverwrites        Overwrite fields with data from locallib: true or false (default)

Example:
\$php downloaddata.php --data=users --roles=all --format=xls > output.xls

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
    'courses' => DD_DATA_COURSES,
    'users' => DD_DATA_USERS
);
if (!isset($options['data']) || !isset($dataoptions[$options['data']])) {
    fputs(STDERR, get_string('invaliddata', 'tool_downloaddata'). "\n");
    fputs(STDERR, $help);
    die();
}
$data = $dataoptions[$options['data']];

$formats = array(
    'csv' => DD_FORMAT_CSV,
    'xls' => DD_FORMAT_XLS
);
if (!isset($options['format']) || !isset($formats[$options['format']])) {
    fputs(STDERR, get_string('invalidformat', 'tool_downloaddata'));
    fputs(STDERR, $help);
    die();
}
$format = $formats[$options['format']];

$encodings = core_text::get_encodings();
if (!isset($encodings[$options['encoding']])) {
    fputs(STDERR, get_string('invalidencoding', 'tool_downloaddata'));
    fputs(STDERR, $help);
    die();
}

$delimiters = csv_import_reader::get_delimiter_list();
if (empty($options['delimiter']) || !isset($delimiters[$options['delimiter']])) {
    fputs(STDERR, get_string('invaliddelimiter', 'tool_downloaddata'));
    fputs(STDERR, $help);
    die();
}

$options['separatesheets'] = ($options['separatesheets'] === true ||
            core_text::strtolower($options['separatesheets']) == 'true');
$options['useoverwrites'] = ($options['useoverwrites'] === true ||
            core_text::strtolower($options['useoverwrites']) == 'true');
$options['sortbycategorypath'] = ($options['sortbycategorypath'] === true ||
            core_text::strtolower($options['sortbycategorypath']) == 'true');

// Emulate admin session.
cron_setup_user();

$contents = NULL;
$roles = NULL;
if ($data == DD_DATA_COURSES) {
    $contents = dd_get_courses($options);
    if (empty($contents)) {
        fputs(STDERR, get_string('emptycontents', 'tool_downloaddata') . "\n");
        die();
    }
} else if ($data == DD_DATA_USERS) {
    $roles = dd_resolve_roles($options['roles']);
    if ($roles == DD_INVALID_ROLES) {
        fputs(STDERR, get_string('invalidroles', 'tool_downloaddata') . "\n");
        die();
    }
    $contents = dd_get_users($roles, $options);
}

$output = "phonyoutput";
if ($format == DD_FORMAT_XLS) {
    $workbook = dd_save_to_excel($data, $output, $options, $contents, $roles);
    $workbook->close();
} else if ($format == DD_FORMAT_CSV) {
    $csv = dd_save_to_csv($data, $output, $options, $contents, $roles);
    $csv->download_file();
}
