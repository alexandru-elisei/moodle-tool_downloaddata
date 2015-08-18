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
    'force' => false,
    'templatecourse' => ''
),
array(
    'h' => 'help',
    'f' => 'format',
    'd' => 'data',
    'l' => 'delimiter',
    'e' => 'encoding',
    't' => 'templatecourse'
));

$help =
"\nDownload Moodle configuration file.

Options:
-h, --help                 Print out this help
-f, --format               Format: csv (default) or xls
-d, --data                 Data to download: courses or teachers
-l, --delimiter            CSV delimiter: colon, semicolon, tab, cfg, comma (default)
-e, --encoding             CSV file encoding: utf8 (default), ... etc
-t, --templatecourse       Template course name
    --force                Force overwriting the output file: true or false (default)

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
    'teachers' => DC_DATA_TEACHERS
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

// Emulate admin session.
cron_setup_user();

$contents = dc_get_data(DC_DATA_COURSES);
if (empty($contents)) {
    fputs(STDERR, get_string('emptycontents', 'tool_downloadconfig') . "\n");
    die();
}

$output = "phonyoutput";
if ($format == DC_FORMAT_XLS) {
    dc_save_to_excel($data, $output, $contents, $options);
} else if ($format == DC_FORMAT_CSV) {
    dc_save_to_csv($data, $output, $contents, $options);
}
