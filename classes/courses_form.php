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
 * File containing the index form.
 *
 * @package    tool_downloaddata
 * @copyright  2015 Alexandru Elisei
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Download courses form.
 *
 * @package    tool_downloaddata
 * @copyright  2015 Alexandru Elisei
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool_downloaddata_courses_form extends moodleform {

    /**
     * The standard form definiton.
     */
    public function definition () {
        $mform = $this->_form;
        $mform->addElement('header', 'generalhdr', get_string('downloadcourses', 'tool_downloaddata'));

        $format_choices = array(
            tool_downloaddata_processor::FORMAT_CSV => 'Comma separated values (.csv)',
            tool_downloaddata_processor::FORMAT_XLS => 'Microsoft Excel 2007 workbook (.xls)'
        );
        $mform->addElement('select', 'format', 
            get_string('format', 'tool_downloaddata'), $format_choices);
        $mform->setDefault('format', 'csv');

        $encodings = core_text::get_encodings();
        $mform->addElement('select', 'encoding', get_string('encoding', 'tool_downloaddata'), $encodings);
        $mform->setDefault('encoding', 'UTF-8');
        $mform->disabledIf('encoding', 'format', 'noteq', tool_downloaddata_processor::FORMAT_CSV);

        $delimiters = csv_import_reader::get_delimiter_list();
        $mform->addElement('select', 'delimiter_name', 
                           get_string('csvdelimiter', 'tool_downloaddata'), $delimiters);
        $mform->setDefault('delimiter_name', 'comma');
        $mform->disabledIf('delimiter_name', 'format', 'noteq', tool_downloaddata_processor::FORMAT_CSV);

        $useoverrides = array('true' => 'Yes', 'false' => 'No');
        $mform->addElement('select', 'useoverrides', 
                           get_string('useoverrides', 'tool_downloaddata'), $useoverrides);
        $mform->addHelpButton('useoverrides', 'useoverrides', 'tool_downloaddata');
        $mform->setDefault('useoverrides', 'false');

        $sortbycategorypath = array('true' => 'Yes', 'false' => 'No');
        $mform->addElement('select', 'sortbycategorypath', 
                           get_string('sortbycategorypath', 'tool_downloaddata'), $sortbycategorypath);
        $mform->setDefault('sortbycategorypath', 'true');
        $mform->addHelpButton('sortbycategorypath', 'sortbycategorypath', 'tool_downloaddata');

        $this->add_action_buttons(false, get_string('download', 'tool_downloaddata'));
    }
}
