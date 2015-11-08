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
 * Link to download data.
 *
 * @package    tool_downloaddata
 * @copyright  2015 Alexandru Elisei
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

// Entry in Site administration -> Courses -> Download courses
if (has_capability('moodle/course:create', context_system::instance())) {
    $ADMIN->add(
        'courses', 
        new admin_externalpage(
            'tooldownloaddata_courses', get_string('downloadcourses', 'tool_downloaddata'),
            "$CFG->wwwroot/$CFG->admin/tool/downloaddata/index_courses.php", 
            'moodle/course:create'
        )
    );
}

// Entry in Site administration -> Users -> Accounts -> Download users
if (has_capability('moodle/site:uploadusers', context_system::instance())) {
    $ADMIN->add(
        'accounts', 
        new admin_externalpage(
            'tooldownloaddata_users', get_string('downloadusers', 'tool_downloaddata'),
            "$CFG->wwwroot/$CFG->admin/tool/downloaddata/index_users.php", 
            'moodle/site:uploadusers'
        )
    );
}
