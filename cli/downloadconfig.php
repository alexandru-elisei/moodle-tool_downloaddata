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
    'input' => '',
    'output' => '',
    'delimiter' => 'comma',
    'encoding' => 'UTF-8',
    'force' => false,
),
array(
    'h' => 'help',
    'f' => 'format',
    'i' => 'input',
    'o' => 'output',
    'd' => 'delimiter',
    'e' => 'encoding',
));

$help =
"\nDownload Moodle configuration file.

Options:
-h, --help                 Print out this help
-f, --format               Format: csv (default) or xls
-i, --input                Configuration file: courses or teachers
-o, --output               Output file
-d, --delimiter            CSV delimiter: colon, semicolon, tab, cfg, comma (default)
-e, --encoding             CSV file encoding: utf8 (default), ... etc
    --force                Force overwriting the output file: true or false (default)

Example:
\$php downloadconfig.php --input=courses --output=./courses.csv --format=csv
";

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help']) {
    echo $help;
    die();
}

$inputs = array(
    'courses' => DC_INPUT_COURSES,
    'teachers' => DC_INPUT_TEACHERS
);
if (!isset($options['input']) || !isset($inputs[$options['input']])) {
    echo get_string('invalidinput', 'tool_downloadconfig');
    echo $help;
    die();
}
$input = $inputs[$options['input']];

$formats = array(
    'csv' => DC_FORMAT_CSV,
    'xls' => DC_FORMAT_XLS
);
if (!isset($options['format']) || !isset($formats[$options['format']])) {
    echo get_string('invalidformat', 'tool_downloadconfig');
    echo $help;
    die();
}
$format = $formats[$options['format']];

$encodings = core_text::get_encodings();
if (!isset($encodings[$options['encoding']])) {
    echo get_string('invalidencoding', 'tool_downloadconfig');
    echo $help;
    die();
}

if (!empty($options['output'])) {
    $options['output'] = realpath($options['output']);
}
if (file_exists($options['output'])) { 
    echo get_string('overwritingfile', 'tool_downloadconfig');
    if (!$options['force']) {
        echo ". Exiting\n";
        echo "To overwrite the file, use the --force switch"; 
        die();
    }
    echo "\n";
}
$output = $options['output'];

$delimiters = csv_import_reader::get_delimiter_list();
if (empty($options['delimiter']) || !isset($delimiters[$options['delimiter']])) {
    echo get_string('invaliddelimiter', 'tool_downloadconfig');
    echo $help;
    die();
}

echo "\nMoodle download configuration file running ...\n\n";

// Emulate admin session.
cron_setup_user();

//dc_export_to_excel($input, $output, $options);
dc_get_data(DC_INPUT_COURSES);

echo "Done.\n";
