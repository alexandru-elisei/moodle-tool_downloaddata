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

    /** @var string[] All valid user roles. */
    protected static $allroles = array();

    /** @var string[] Requested course fields. */
    protected static $coursefields = array();

    /** @var string[] Requested user fields. */
    protected static $userfields = array();

    /** @var array Options for downloading users in csv format. */
    protected static $optionsuserscsv = array(
        'data' => tool_downloaddata_processor::DATA_USERS,
        'format' => tool_downloaddata_processor::FORMAT_CSV,
        'delimiter' => 'comma',
        'encoding' => 'UTF-8',
        'useoverrides' => false,
        'sortbycategorypath' => false,
    );

    /** @var array Options for downloading users in xls format. */
    protected static $optionsusersxls = array(
        'data' => tool_downloaddata_processor::DATA_USERS,
        'format' => tool_downloaddata_processor::FORMAT_XLS,
        'delimiter' => 'comma',
        'encoding' => 'UTF-8',
        'useoverrides' => false,
        'sortbycategorypath' => true,
    );

    /** @var array Options for downloading courses in csv format. */
    protected static $optionscoursescsv = array(
        'data' => tool_downloaddata_processor::DATA_COURSES,
        'format' => tool_downloaddata_processor::FORMAT_CSV,
        'delimiter' => 'comma',
        'encoding' => 'UTF-8',
        'useoverrides' => false,
        'sortbycategorypath' => true,
    );

    /** @var array Options for downloading courses in xls format. */
    protected static $optionscoursesxls = array(
        'data' => tool_downloaddata_processor::DATA_COURSES,
        'format' => tool_downloaddata_processor::FORMAT_XLS,
        'delimiter' => 'comma',
        'encoding' => 'UTF-8',
        'useoverrides' => false,
        'sortbycategorypath' => true,
    );

    /**
     * Getting all the valid roles.
     */
    public static function setUpBeforeClass() {
        self::$allroles = tool_downloaddata_processor::get_all_valid_roles();
        self::$coursefields = tool_downloaddata_config::$coursefields;
        self::$userfields = tool_downloaddata_config::$userfields;
    }

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

        $fields = self::$coursefields;
        $this->setExpectedException('moodle_exception', get_string('invaliddata', 'tool_downloaddata'));
        $processor = new tool_downloaddata_processor($options, $fields);
    }

    /**
     * Tests if specifying invalid download data throws an exception.
     */
    public function test_invalid_options_data() {
        $this->resetAfterTest(true);

        $options = self::$optionscoursescsv;
        $options['data'] = 4;
        $fields = self::$coursefields;
        $this->setExpectedException('moodle_exception', get_string('invaliddata', 'tool_downloaddata'));
        $processor = new tool_downloaddata_processor($options, $fields);
    }

    /**
     * Tests if specifying an invalid format throws an exception.
     */
    public function test_invalid_options_format() {
        $this->resetAfterTest(true);

        $options = array();
        $options['data'] = tool_downloaddata_processor::DATA_COURSES;
        $options['format'] = 10;
        $fields = self::$coursefields;
        $this->setExpectedException('moodle_exception', get_string('invalidformat', 'tool_downloaddata'));
        $processor = new tool_downloaddata_processor($options, $fields);
    }

    /**
     * Tests if specifying an invalid delimiter throws an exception.
     */
    public function test_invalid_options_delimiter() {
        $this->resetAfterTest(true);

        $options = self::$optionscoursescsv;
        $options['delimiter'] = 'invalid';
        $fields = self::$coursefields;
        $this->setExpectedException('moodle_exception', get_string('invaliddelimiter', 'tool_downloaddata'));
        $processor = new tool_downloaddata_processor($options, $fields);
    }

    /**
     * Tests if specifying an invalid encoding throws an exception.
     */
    public function test_invalid_options_encoding() {
        $this->resetAfterTest(true);

        $options = self::$optionscoursescsv;
        $options['encoding'] = 'invalid';
        $fields = self::$coursefields;
        $this->setExpectedException('moodle_exception', get_string('invalidencoding', 'tool_uploadcourse'));
        $processor = new tool_downloaddata_processor($options, $fields);
    }

    /**
     * Tests if using overrides without an override fields array throws an exception.
     */
    public function test_empty_overrides() {
        $this->resetAfterTest(true);

        $options = self::$optionscoursescsv;
        $options['useoverrides'] = true;
        $fields = self::$coursefields;
        $overrides = array();
        $this->setExpectedException('moodle_exception', get_string('emptyoverrides', 'tool_downloaddata'));
        $processor = new tool_downloaddata_processor($options, $fields, null, $overrides);
    }

    /**
     * Tests the tool_downloaddata_processor constructor for users.
     */
    public function test_constructor_users() {
        $this->resetAfterTest(true);

        $options = self::$optionsuserscsv;
        $fields = self::$userfields;
        $roles = self::$allroles;
        $processor = new tool_downloaddata_processor($options, $fields, $roles);
        $this->assertInstanceOf('tool_downloaddata_processor', $processor);
    }

    /**
     * Tests the tool_downloaddata_processor constructor for courses.
     */
    public function test_constructor_courses() {
        $this->resetAfterTest(true);

        $options = self::$optionscoursescsv;
        $fields = self::$coursefields;
        $processor = new tool_downloaddata_processor($options, $fields);
        $this->assertInstanceOf('tool_downloaddata_processor', $processor);
    }

    /**
     * Tests if preparing the same file twice throws an exception.
     */
    public function test_process_started() {
        $this->resetAfterTest(true);

        $options = self::$optionscoursescsv;
        $fields = self::$coursefields;
        $processor = new tool_downloaddata_processor($options, $fields);
        $processor->prepare();
        $this->setExpectedException('coding_exception', get_string('processstarted', 'tool_downloaddata'));
        $processor->prepare();
    }

    /**
     * Tests if requesting an invalid course field throws an exception.
     */
    public function test_invalid_course_fields() {
        $this->resetAfterTest(true);

        $options = self::$optionscoursescsv;
        $invalidfield = 'test';
        $fields = array($invalidfield);
        $processor = new tool_downloaddata_processor($options, $fields);
        $this->setExpectedException('moodle_exception',
                                    get_string('invalidfield', 'tool_downloaddata', $invalidfield));
        $processor->prepare();
    }

    /**
     * Tests if requesting an invalid user field throws an exception.
     */
    public function test_invalid_user_fields() {
        $this->resetAfterTest(true);

        $options = self::$optionsuserscsv;
        $invalidfield = 'test';
        $fields = array($invalidfield);
        $roles = self::$allroles;
        $processor = new tool_downloaddata_processor($options, $fields, $roles);
        $this->setExpectedException('moodle_exception',
                                    get_string('invalidfield', 'tool_downloaddata', $invalidfield));
        $processor->prepare();
    }

    /**
     * Tests if accessing the file object before preparing the file throws an exception.
     */
    public function test_file_not_prepared() {
        $this->resetAfterTest(true);

        $options = self::$optionscoursescsv;
        $fields = self::$coursefields;
        $processor = new tool_downloaddata_processor($options, $fields);
        $this->setExpectedException('coding_exception', get_string('filenotprepared', 'tool_downloaddata'));
        $processor->get_file_object();
    }

    /**
     * Tests if requesting an invalid role throws an exception.
     */
    public function test_invalid_role() {
        $this->resetAfterTest(true);

        $options = self::$optionsuserscsv;
        $invalidrole = 'invalid';
        $roles = array($invalidrole);
        $fields = self::$userfields;
        $processor = new tool_downloaddata_processor($options, $fields, $roles);
        $this->setExpectedException('moodle_exception',
                                    get_string('invalidrole', 'tool_downloaddata', $invalidrole));
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

        $options = self::$optionscoursescsv;
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
        $options = self::$optionscoursescsv;
        $options['useoverrides'] = true;
        $processor = new tool_downloaddata_processor($options, $fields, null, $overrides);
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
        $options = self::$optionscoursescsv;
        $options['useoverrides'] = true;
        $options['sortbycategorypath'] = true;
        $processor = new tool_downloaddata_processor($options, $fields, null, $overrides);
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
     * Tests downloading users when no user accounts are present.
     */
    public function test_download_users_no_users() {
        $this->resetAfterTest(true);

        $fields = array(
            'username'
        );
        $options = self::$optionsuserscsv;
        $roles = self::$allroles;
        $processor = new tool_downloaddata_processor($options, $fields, $roles);
        $processor->prepare();
        $csv = $processor->get_file_object();

        $expectedoutput = array(
            'username'
        );
        $expectedoutput = implode("\n", $expectedoutput);
        $output = rtrim($csv->print_csv_data(true));
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
                break;
            }
        }
        $this->getDataGenerator()->enrol_user($user->id, $course->id, $role->id);

        $fields = array(
            'username'
        );
        $options = self::$optionsuserscsv;
        // Created a new role, cannot use self::$allroles.
        $roles = tool_downloaddata_processor::get_all_valid_roles();
        $processor = new tool_downloaddata_processor($options, $fields, $roles);
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
                break;
            }
        }
        $this->getDataGenerator()->enrol_user($user->id, $course->id, $role->id);

        $fields = array(
            'username'
        );
        $options = self::$optionsuserscsv;
        // Created a new role, cannot use self::$allroles.
        $roles = tool_downloaddata_processor::get_all_valid_roles();
        $options['useoverrides'] = true;
        $overrides = array(
            'test'  => 'test'
        );
        $processor = new tool_downloaddata_processor($options, $fields, $roles, $overrides);
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

    /**
     * Tests downloading multiple users with variable number of roles to csv.
     */
    public function test_download_multiple_users_to_csv() {
        global $DB;
        $this->resetAfterTest(true);

        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $roleid1 = $this->getDataGenerator()->create_role();
        $roleid2 = $this->getDataGenerator()->create_role();
        $roles = get_all_roles();
        foreach ($roles as $r) {
            if ($roleid1 == $r->id) {
                $role1 = $r;
            } else if ($roleid2 == $r->id) {
                $role2 = $r;
            }
        }
        $this->getDataGenerator()->enrol_user($user1->id, $course1->id, $role1->id);
        $this->getDataGenerator()->enrol_user($user2->id, $course1->id, $role1->id);
        $this->getDataGenerator()->enrol_user($user2->id, $course2->id, $role2->id);

        $fields = array(
            'username'
        );
        $options = self::$optionsuserscsv;
        // Created new roles, cannot use self::$allroles.
        $roles = tool_downloaddata_processor::get_all_valid_roles();
        $options['sortbycategorypath'] = true;
        $processor = new tool_downloaddata_processor($options, $fields, $roles);
        $processor->prepare();
        $csv = $processor->get_file_object();

        $expectedoutput = array(
            'username,course1,role1,course2,role2',
            $user1->username . ',' . $course1->shortname . ',' . $role1->shortname . ',,',
            $user2->username . ',' . $course1->shortname . ',' . $role1->shortname . ',' .
                                     $course2->shortname . ',' . $role2->shortname,
        );
        $expectedoutput = implode("\n", $expectedoutput);
        $output = rtrim($csv->print_csv_data(true));
        // Sorting the output lines as the user order is random.
        $output = explode("\n", $output);
        sort($output);
        $output = implode("\n", $output);
        $output = rtrim($output);
        $this->assertEquals($expectedoutput, $output);
    }
}
