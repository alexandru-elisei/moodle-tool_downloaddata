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
 * Download data configuration options.
 *
 * @package    tool_downloaddata
 * @copyright  2015 Alexandru Elisei
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Class with configuration options.
 */
class tool_downloaddata_config {
    
    /**
     * Column widths for xls (Excel 2007) file format.
     */
    public static $columnwidths = array(
        'default' => 13,
        'category_path' => 30,
        'email' => 30,
        'username' => 16
    );

    /**
     * Output fields for courses.
     */
    public static $coursefields = array(
        'shortname',
        'fullname',
        'category_path',
    );

    /**
     * Output fields for users.
     */
    public static $userfields = array(
        'username',
        'firstname',
        'lastname',
        'email',
        'auth'
    );

    /**
     * Default worksheet names when not using separate worksheets.
     */
    public static $worksheetnames = array(
        'users' => 'users',
        'courses' => 'courses',
    );

    /**
     * Overwrite values for course fields. Array of 'field' => 'overwrite value' 
     * elements.
     */
    public static $courseoverwrites = array();

    /**
     * Overwrite values for user fields. Array of 'field' => 'overwrite value' 
     * elements.
     */
    public static $useroverwrites = array();
}
