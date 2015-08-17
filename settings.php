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
 * Link to download config.
 *
 * @package    tool
 * @subpackage downloadconfig
 * @copyright  2015 Alexandru Elisei
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

// Entry in Site administration -> Reports -> Download configuration
if (has_capability('moodle/course:create', context_system::instance())) {
    //$ADMIN->add('root', new admin_category('upb_curs', 'Cursuri UPB'));
    $ADMIN->add(
        'reports', 
        new admin_externalpage(
            'tooldownloadconfig', get_string('downloadconfig', 'tool_downloadconfig'),
            "$CFG->wwwroot/$CFG->admin/tool/downloadconfig/index.php", 
            'moodle/course:create'
        )
    );
}
