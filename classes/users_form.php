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
 * Download users form.
 *
 * @package    tool_downloaddata
 * @copyright  2015 Alexandru Elisei
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool_downloaddata_users_form extends moodleform {

    /**
     * The standard form definiton.
     */
    public function definition () {
        $mform = $this->_form;
        $mform->addElement('header', 'generalhdr', get_string('downloadusersbyrole', 'tool_downloaddata'));

        $allroles = get_all_roles();
        $roles = array();
        foreach ($allroles as $key => $role) {
            // Ignoring system roles.
            $isguest = ($role->shortname == 'guest');
            $isfrontpage = ($role->shortname == 'frontpage');
            $isadmin = ($role->shortname == 'admin');
            if (!$isguest && !$isfrontpage && !$isadmin) {
                $roles[$role->shortname] = $role->shortname;
            }
        }
        $roles['all'] = 'All';
        $mform->addElement('select', 'roles', get_string('roles', 'tool_downloaddata'), $roles);
        $mform->setDefault('roles', 'editingteacher');
        $mform->addHelpButton('roles', 'roles', 'tool_downloaddata');

        $formatchoices = array(
            tool_downloaddata_processor::FORMAT_CSV => get_string('formatcsv', 'tool_downloaddata'),
            tool_downloaddata_processor::FORMAT_XLS => get_string('formatxls', 'tool_downloaddata')
        );
        $mform->addElement('select', 'format',
            get_string('format', 'tool_downloaddata'), $formatchoices);
        $mform->setDefault('format', tool_downloaddata_processor::FORMAT_CSV);

        $encodings = core_text::get_encodings();
        $mform->addElement('select', 'encoding', get_string('encoding', 'tool_downloaddata'), $encodings);
        $mform->setDefault('encoding', 'UTF-8');
        $mform->disabledIf('encoding', 'format', 'noteq', tool_downloaddata_processor::FORMAT_CSV);

        $delimiters = csv_import_reader::get_delimiter_list();
        $mform->addElement('select', 'delimiter_name',
                           get_string('csvdelimiter', 'tool_downloaddata'), $delimiters);
        $mform->setDefault('delimiter_name', 'comma');
        $mform->disabledIf('delimiter_name', 'format', 'noteq', tool_downloaddata_processor::FORMAT_CSV);

        $usedefaults = array('true' => 'Yes', 'false' => 'No');
        $mform->addElement('select', 'usedefaults',
                           get_string('usedefaults', 'tool_downloaddata'), $usedefaults);
        $mform->addHelpButton('usedefaults', 'usedefaults', 'tool_downloaddata');
        $mform->setDefault('usedefaults', 'true');

        $useoverrides = array('true' => 'Yes', 'false' => 'No');
        $mform->addElement('select', 'useoverrides',
                           get_string('useoverrides', 'tool_downloaddata'), $useoverrides);
        $mform->addHelpButton('useoverrides', 'useoverrides', 'tool_downloaddata');
        $mform->setDefault('useoverrides', 'false');

        $mform->addElement('header', 'fieldshdr', get_string('fields', 'tool_downloaddata'));
        $mform->setExpanded('fieldshdr', false);

        $mform->addElement('textarea', 'fields', get_string('fields', 'tool_downloaddata'),
                           'wrap="virtual" rows="4" cols="40"');
        $mform->setType('fields', PARAM_RAW);
        $mform->addHelpButton('fields', 'fields', 'tool_downloaddata');

        $mform->addElement('header', 'overrideshdr', get_string('overrides', 'tool_downloaddata'));
        $mform->setExpanded('overrideshdr', false);

        $mform->addElement('textarea', 'overrides', get_string('overrides', 'tool_downloaddata'),
                           'wrap="virtual" rows="4" cols="40"');
        $mform->setType('overrides', PARAM_RAW);
        $mform->addHelpButton('overrides', 'overrides', 'tool_downloaddata');

        $this->add_action_buttons(false, get_string('download', 'tool_downloaddata'));
    }
}
