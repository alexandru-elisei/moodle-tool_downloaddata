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
 * File containing the download form.
 *
 * @package    tool_downloadconfig
 * @copyright  2015 Alexandru Elisei
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');

/**
 * Download site configuration.
 *
 * @package    tool_downloadconfig
 * @copyright  2015 Alexandru Elisei
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool_downloadconfig_form extends moodleform {

    /**
     * The standard form definiton.
     * @return void
     */
    public function definition () {
        $mform = $this->_form;

        $mform->addElement('header', 'generalhdr', get_string('download'));

        $this->add_action_buttons(false, get_string('download', 'tool_downloadconfig'));
        $this->add_action_buttons(false, get_string('upload', 'tool_downloadconfig'));
    }
}
