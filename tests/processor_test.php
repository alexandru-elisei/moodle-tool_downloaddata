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
 * File containing tests for the processor.
 *
 * @package    tool_downloaddata
 * @copyright  2015 Alexandru Elisei
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/user/lib.php');
require_once($CFG->dirroot . '/enrol/locallib.php');
require_once($CFG->libdir . '/coursecatlib.php');

/**
 * Processor test case.
 *
 * @package    tool_downloaddata
 * @copyright  2015 Alexandru Elisei
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool_downloaddata_processor_testcase extends advanced_testcase {

    /**
     * Options for downloading users in csv format.
     */
    protected static $options_users_csv = array(
        'data' => tool_downloaddata_processor::DATA_USERS,
        'format' => tool_downloaddata_processor::FORMAT_CSV,
        'delimiter' => 'comma',
        'encoding' => 'UTF-8',
        'roles' => 'all',
        'useoverrides' => false,
        'sortbycategorypath' => false,
    );

    /**
     * Options for downloading users in xls format.
     */
    protected static $options_users_xls = array(
        'data' => tool_downloaddata_processor::DATA_USERS,
        'format' => tool_downloaddata_processor::FORMAT_XLS,
        'delimiter' => 'comma',
        'encoding' => 'UTF-8',
        'roles' => 'all',
        'useoverrides' => false,
        'sortbycategorypath' => true,
    );

    /**
     * Options for downloading courses in csv format.
     */
    protected static $options_courses_csv = array(
        'data' => tool_downloaddata_processor::DATA_COURSES,
        'format' => tool_downloaddata_processor::FORMAT_CSV,
        'delimiter' => 'comma',
        'encoding' => 'UTF-8',
        'roles' => 'all',
        'useoverrides' => false,
        'sortbycategorypath' => true,
    );

    /**
     * Options for downloading courses in xls format.
     */
    protected static $options_courses_xls = array(
        'data' => tool_downloaddata_processor::DATA_COURSES,
        'format' => tool_downloaddata_processor::FORMAT_XLS,
        'delimiter' => 'comma',
        'encoding' => 'UTF-8',
        'roles' => 'all',
        'useoverrides' => false,
        'sortbycategorypath' => true,
    );

    /**
     * Tidy up open files that may be left open.
     */
    protected function tearDown() {
        gc_collect_cycles();
    }

    /**
     * Tests if specifying no download data throws an exception.
     */
    public function test_empty_options_data() {
        $this->resetAfterTest(true);
        $options = array();

        $fields = tool_downloaddata_config::$userfields;
        $this->setExpectedException('coding_exception', get_string('invaliddata', 'tool_downloaddata'));
        $processor = new tool_downloaddata_processor($options, $fields);
    }

    /**
     * Tests if specifying invalid download data throws an exception.
     */
    public function test_invalid_options_data() {
        $this->resetAfterTest(true);

        $options = self::$options_users_csv;
        $options['data'] = 4;
        $fields = tool_downloaddata_config::$userfields;
        $this->setExpectedException('coding_exception', get_string('invaliddata', 'tool_downloaddata'));
        $processor = new tool_downloaddata_processor($options, $fields);
    }

    /**
     * Tests if specifying an invalid format throws an exception.
     */
    public function test_invalid_options_format() {
        $this->resetAfterTest(true);

        $options = array();
        $options['data'] = tool_downloaddata_processor::DATA_USERS;
        $options['format'] = 10;
        $fields = tool_downloaddata_config::$userfields;
        $this->setExpectedException('coding_exception', get_string('invalidformat', 'tool_downloaddata'));
        $processor = new tool_downloaddata_processor($options, $fields);
    }

    /**
     * Tests if specifying an invalid delimiter throws an exception.
     */
    public function test_invalid_options_delimiter() {
        $this->resetAfterTest(true);
        
        $options = self::$options_users_csv;
        $options['delimiter'] = 'invalid';
        $fields = tool_downloaddata_config::$userfields;
        $this->setExpectedException('coding_exception', get_string('invaliddelimiter', 'tool_downloaddata'));
        $processor = new tool_downloaddata_processor($options, $fields);
    }

    /**
     * Tests if specifying an invalid encoding throws an exception.
     */
    public function test_invalid_options_encoding() {
        $this->resetAfterTest(true);

        $options = self::$options_users_csv;
        $options['encoding'] = 'invalid';
        $fields = tool_downloaddata_config::$userfields;
        $this->setExpectedException('coding_exception', get_string('invalidencoding', 'tool_uploadcourse'));
        $processor = new tool_downloaddata_processor($options, $fields);
    }
 
    /**
     * Tests if using overrides without an override fields array throws an exception.
     */
    public function test_empty_overrides() {
        $this->resetAfterTest(true);

        $options = self::$options_users_csv;
        $options['useoverrides'] = true;
        $fields = tool_downloaddata_config::$userfields;
        $overrides = array();
        $this->setExpectedException('coding_exception', get_string('emptyoverrides', 'tool_downloaddata'));
        $processor = new tool_downloaddata_processor($options, $fields, $overrides);
    }

    /**
     * Tests the tool_downloaddata_processor constructor.
     */
    public function test_constructor() {
        $this->resetAfterTest(true);

        $options = self::$options_users_csv;
        $fields = tool_downloaddata_config::$userfields;
        $processor = new tool_downloaddata_processor($options, $fields);
        $this->assertInstanceOf('tool_downloaddata_processor', $processor);
    }

    /**
     * Tests if preparing the same file twice throws an exception.
     */
    public function test_process_started() {
        $this->resetAfterTest(true);

        $options = self::$options_users_csv;
        $fields = tool_downloaddata_config::$userfields;
        $processor = new tool_downloaddata_processor($options, $fields);
        $processor->prepare();
        $this->setExpectedException('coding_exception', get_string('processstarted', 'tool_downloaddata'));
        $processor->prepare();
    }

    /**
     * Tests if accessing the file object before preparing the file throws an exception.
     */
    public function test_file_not_prepared() {
        $this->resetAfterTest(true);

        $options = self::$options_users_csv;
        $fields = tool_downloaddata_config::$userfields;
        $processor = new tool_downloaddata_processor($options, $fields);
        $this->setExpectedException('coding_exception', get_string('filenotprepared', 'tool_downloaddata'));
        $processor->get_file_object();
    }

    /**
     * Tests if requesting an invalid role throws an exception.
     */
    public function test_invalid_role() {
        $this->resetAfterTest(true);

        $options = self::$options_users_csv;
        $options['roles'] = 'invalid';
        $fields = tool_downloaddata_config::$userfields;
        $processor = new tool_downloaddata_processor($options, $fields);
        $this->setExpectedException('coding_exception', get_string('invalidrole', 'tool_downloaddata'));
        $processor->prepare();
    }
   
    /**
     * Tests downloading course data.
     */
    public function test_download_course() {
        global $DB;
        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course();
        $fields = array(
            'shortname',
            'fullname'
        );

        $options = self::$options_courses_csv;
        $processor = new tool_downloaddata_processor($options, $fields);
        $processor->prepare();
        $csv = $processor->get_file_object();

        $expectedoutput = array(
            'shortname,fullname',
            $course->shortname . ',"' . $course->fullname . '"'
        );
        $expectedoutput = implode("\n", $expectedoutput);
        $output = $csv->print_csv_data(true);
        // Removing implicit phpunit course.
        $output = preg_replace('/phpunit(.)*\n/', '', $output);
        $output = rtrim($output);
        $this->assertEquals($expectedoutput, $output);
    }

    /**
     * Tests downloading course data with override fields.
     */
    public function test_download_course_useoverrides() {
        global $DB;
        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course();
        $fields = array(
            'shortname',
            'fullname'
        );
        $overrides = array(
            'test' => 'test',
        );
        $options = self::$options_courses_csv;
        $options['useoverrides'] = true;
        $processor = new tool_downloaddata_processor($options, $fields, $overrides);
        $processor->prepare();
        $csv = $processor->get_file_object();

        $expectedoutput = array(
            'shortname,fullname,test',
            $course->shortname . ',"' . $course->fullname . '",test'
        );
        $expectedoutput = implode("\n", $expectedoutput);
        $output = $csv->print_csv_data(true);
        // Removing implicit phpunit course.
        $output = preg_replace('/phpunit(.)*\n/', '', $output);
        $output = rtrim($output);
        $this->assertEquals($expectedoutput, $output);
    }

    /**
     * Tests downloading course data with override fields while sorting by category path.
     */
    public function test_download_course_useoverrides_sortbycategorypath() {
        global $DB;
        $this->resetAfterTest(true);

        $category1 = $this->getDataGenerator()->create_category(array( 'name' => 'Z'));
        $category2 = $this->getDataGenerator()->create_category(array( 'name' => 'A'));
        // Courses are downloaded in the order they were created.
        $course1 = $this->getDataGenerator()->create_course(array('category' => $category1->id));
        $course2 = $this->getDataGenerator()->create_course(array('category' => $category2->id));

        $fields = array(
            'shortname',
            'fullname',
            'category_path'
        );
        $overrides = array(
            'test' => 'test',
        );
        $options = self::$options_courses_csv;
        $options['useoverrides'] = true;
        $options['sortbycategorypath'] = true;
        $processor = new tool_downloaddata_processor($options, $fields, $overrides);
        $processor->prepare();
        $csv = $processor->get_file_object();

        $expectedoutput = array(
            'shortname,fullname,category_path,test',
            $course2->shortname . ',"' . $course2->fullname . '",' . $category2->name . ',test',
            $course1->shortname . ',"' . $course1->fullname . '",' . $category1->name . ',test',
        );
        $expectedoutput = implode("\n", $expectedoutput);
        $output = $csv->print_csv_data(true);
        // Removing implicit phpunit course.
        $output = preg_replace('/phpunit(.)*\n/', '', $output);
        $output = rtrim($output);
        $this->assertEquals($expectedoutput, $output);
    }

    /**
     * Tests downloading users.
     */
    public function test_download_users() {
        global $DB;
        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $roleid = $this->getDataGenerator()->create_role();
        $roles = get_all_roles();
        foreach ($roles as $r) {
            if ($roleid == $r->id) {
                $role = $r;
            }
        }
        $this->getDataGenerator()->enrol_user($user->id, $course->id, $role->id);

        $fields = array(
            'username'
        );
        $options = self::$options_users_csv;
        $options['roles'] = $role->shortname;
        $processor = new tool_downloaddata_processor($options, $fields);
        $processor->prepare();
        $csv = $processor->get_file_object();

        $expectedoutput = array(
            'username,course1,role1',
            $user->username . ',' . $course->shortname . ',' . $role->shortname
        );
        $expectedoutput = implode("\n", $expectedoutput);
        $output = rtrim($csv->print_csv_data(true));
        $this->assertEquals($expectedoutput, $output);
    }
    
    /**
     * Tests downloading users and using overrides.
     */
    public function test_download_users_useoverrides() {
        global $DB;
        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $roleid = $this->getDataGenerator()->create_role();
        $roles = get_all_roles();
        foreach ($roles as $r) {
            if ($roleid == $r->id) {
                $role = $r;
            }
        }
        $this->getDataGenerator()->enrol_user($user->id, $course->id, $role->id);

        $fields = array(
            'username'
        );
        $options = self::$options_users_csv;
        $options['roles'] = $role->shortname;
        $options['useoverrides'] = true;
        $overrides = array(
            'test'  => 'test'
        );
        $processor = new tool_downloaddata_processor($options, $fields, $overrides);
        $processor->prepare();
        $csv = $processor->get_file_object();

        $expectedoutput = array(
            'username,test,course1,role1',
            $user->username . ',test,' . $course->shortname . ',' . $role->shortname
        );
        $expectedoutput = implode("\n", $expectedoutput);
        $output = rtrim($csv->print_csv_data(true));
        $this->assertEquals($expectedoutput, $output);
    }
}
