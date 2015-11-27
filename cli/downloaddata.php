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
 * CLI script for downloading users or courses.
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

// Now get cli options.
list($options, $unrecognized) = cli_get_params(array(
    'data' => '',
    'delimiter' => 'comma',
    'encoding' => 'UTF-8',
    'fields' => '',
    'format' => 'csv',
    'help' => false,
    'roles' => 'all',
    'overrides' => '',
    'sortbycategorypath' => false,
    'usedefaults' => true,
    'useoverrides' => false,
),
array(
    'd' => 'data',
    'l' => 'delimiter',
    'e' => 'encoding',
    'i' => 'fields',
    'f' => 'format',
    'h' => 'help',
    'o' => 'overrides',
    'r' => 'roles',
    's' => 'sortbycategorypath'
));

$help =
"\nDownload Moodle data file.

Options:
-d, --data                 Data to download: courses or users
-l, --delimiter            CSV delimiter: colon, semicolon, tab, cfg, comma (default)
-e, --encoding             CSV file encoding: utf8 (default), ... etc
-i, --fields               Fields to print, comma separated. If absent, the fields in ../config.php are used
-f, --format               Format: csv (default) or xls
-h, --help                 Print out this help
-r, --roles                Specific roles for users (comma separated) or all roles
-o, --overrides            Override fields, comma separated, in the form field=value. Ignored when useoverrides is false
-s, --sortbycategorypath   Sort courses by category path alphabetically: true (default) or false
    --usedefaults          Use default values from DOWNLOADDATA_DIRECTORY/config.php for fields and overrides: true (default) or false. NOTE: Values given as arguments replace the default values
    --useoverrides         Override fields with data from locallib: true or false (default)

Example:
\$php downloaddata.php --data=users --roles=all --format=xls > output.xls

";

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help']) {
    echo $help;
    die();
}

$dataoptions = array(
    'courses' => tool_downloaddata_processor::DATA_COURSES,
    'users' => tool_downloaddata_processor::DATA_USERS
);
if (!isset($options['data']) || !isset($dataoptions[$options['data']])) {
    echo "\n" . get_string('invaliddata', 'tool_downloaddata') . "!\n";
    echo $help;
    die();
}
$options['data'] = $dataoptions[$options['data']];

$formats = array(
    'csv' => tool_downloaddata_processor::FORMAT_CSV,
    'xls' => tool_downloaddata_processor::FORMAT_XLS
);
if (!isset($options['format']) || !isset($formats[$options['format']])) {
    echo "\n" . get_string('invalidformat', 'tool_downloaddata') . "!\n";
    echo $help;
    die();
}
$options['format'] = $formats[$options['format']];

$encodings = core_text::get_encodings();
if (!isset($encodings[$options['encoding']])) {
    echo "\n" . get_string('invalidencoding', 'tool_downloaddata') . "!\n";
    echo $help;
    die();
}

$delimiters = csv_import_reader::get_delimiter_list();
if (empty($options['delimiter']) || !isset($delimiters[$options['delimiter']])) {
    echo "\n" . get_string('invaliddelimiter', 'tool_downloaddata') . "!\n";
    echo $help;
    die();
}

$overrides = array();
$options['useoverrides'] = ($options['useoverrides'] === true ||
                            core_text::strtolower($options['useoverrides']) == 'true');
$options['usedefaults'] = ($options['usedefaults'] === true ||
                            core_text::strtolower($options['usedefaults']) == 'true');
$options['sortbycategorypath'] = ($options['sortbycategorypath'] === true ||
                                  core_text::strtolower($options['sortbycategorypath']) == 'true');

// Emulate admin session.
cron_setup_user();

$fields = array();
if (!empty($options['fields'])) {
    $fields = explode(',', $options['fields']);
    foreach ($fields as $key => $field) {
        $fields[$key] = trim($field);
    }
}
if ($options['useoverrides']) {
    if (!empty($options['overrides'])) {
        $o = explode(',', $options['overrides']);
        foreach ($o as $value) {
            $override = explode('=', $value);
            $overrides[trim($override[0])] = trim($override[1]);
        }
    }
}

if ($options['data'] == tool_downloaddata_processor::DATA_USERS) {
    if (empty($fields) && $options['usedefaults']) {
        $fields = tool_downloaddata_config::$userfields;
    }
    if ($options['useoverrides'] && empty($overrides) && $options['usedefaults']) {
        $overrides = tool_downloaddata_config::$useroverrides;
    }
} else if ($options['data'] == tool_downloaddata_processor::DATA_COURSES) {
    if (empty($fields) && $options['usedefaults']) {
        $fields = tool_downloaddata_config::$coursefields;
    }
    if ($options['useoverrides'] && empty($overrides) && $options['usedefaults']) {
        $overrides = tool_downloaddata_config::$courseoverrides;
    }
}

if (empty($fields)) {
    echo "\n" . get_string('emptyfields', 'tool_downloaddata') . "!\n";
    echo $help;
    die();
}
if ($options['useoverrides'] && empty($overrides)) {
    echo "\n" . get_string('emptyoverrides', 'tool_downloaddata') . "!\n";
    echo $help;
    die();
}

$processor = new tool_downloaddata_processor($options, $fields, $overrides);
$processor->prepare();
$processor->download();
/*
$csv = $processor->get_file_object();
$csv->print_csv_data(true);
 */
/*
$xls = $processor->get_file_object();
$xls->send('test.xls');
$xls->close();
 */

/*
$contents = null;
$roles = null;
if ($data == TOOL_DOWNLOADDATA_DATA_COURSES) {
    $contents = tool_downloaddata_get_courses($options);
    if (empty($contents)) {
        throw new coding_exception(get_string('emptycontents', 'tool_downloaddata'));
    }
} else if ($data == TOOL_DOWNLOADDATA_DATA_USERS) {
    $roles = tool_downloaddata_resolve_roles($options['roles']);
    if ($roles == TOOL_DOWNLOADDATA_INVALID_ROLES) {
        fputs(STDERR, get_string('invalidrole', 'tool_downloaddata') . "\n");
        die();
    }
    $contents = tool_downloaddata_get_users($roles, $options);
}

$output = 'phonyoutput';
if ($format == TOOL_DOWNLOADDATA_FORMAT_XLS) {
    $workbook = tool_downloaddata_save_to_excel($data, $output, $options, $contents, $roles);
    $workbook->close();
} else if ($format == TOOL_DOWNLOADDATA_FORMAT_CSV) {
    $csv = tool_downloaddata_save_to_csv($data, $output, $options, $contents, $roles);
    $csv->download_file();
}
 */
