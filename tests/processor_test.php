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

/**
 * Processor test case.
 *
 * @package    tool_downloaddata
 * @copyright  2015 Alexandru Elisei
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool_downloaddata_processor_testcase extends advanced_testcase {

    /**
     * Tidy up open files that may be left open.
     */
    protected function tearDown() {
        gc_collect_cycles();
    }

    public function test_empty_options_data() {
        $this->resetAfterTest(true);
        $options = array();

        $this->setExpectedException('coding_exception', get_string('invaliddata', 'tool_downloaddata'));
        $processor = new tool_downloaddata_processor($options);
    }

    public function test_invalid_options_data() {
        $this->resetAfterTest(true);
        $options = array();
        $options['data'] = 4;

        $this->setExpectedException('coding_exception', get_string('invaliddata', 'tool_downloaddata'));
        $processor = new tool_downloaddata_processor($options);
    }

    public function test_invalid_options_format() {
        $this->resetAfterTest(true);
        $options = array();
        $options['data'] = tool_downloaddata_processor::DATA_USERS;
        $options['format'] = 10;

        $this->setExpectedException('coding_exception', get_string('invalidformat', 'tool_downloaddata'));
        $processor = new tool_downloaddata_processor($options);
    }

    public function test_invalid_options_delimiter() {
        $this->resetAfterTest(true);
        $options = array();
        $options['data'] = tool_downloaddata_processor::DATA_USERS;
        $options['delimiter'] = 'invalid';

        $this->setExpectedException('coding_exception', get_string('invaliddelimiter', 'tool_downloaddata'));
        $processor = new tool_downloaddata_processor($options);
    }

    public function test_invalid_options_encoding() {
        $this->resetAfterTest(true);
        $options = array();
        $options['data'] = tool_downloaddata_processor::DATA_USERS;
        $options['encoding'] = 'invalid';

        $this->setExpectedException('coding_exception', get_string('invalidencoding', 'tool_uploadcourse'));
        $processor = new tool_downloaddata_processor($options);
    }

    public function test_valid_options() {
        $this->resetAfterTest(true);
        $options = array();
        $options['data'] = tool_downloaddata_processor::DATA_USERS;
        $options['format'] = tool_downloaddata_processor::FORMAT_XLS;
        $options['delimiter'] = 'comma';
        $options['encoding'] = 'UTF-8';
        $options['roles'] = 'student,editingteacher';
        $options['useoverwrites'] = true;
        $options['sortbycategorypath'] = true;
        $processor = new tool_downloaddata_processor($options);

        $this->assertInstanceOf('tool_downloaddata_processor', $processor);
    }

    public function test_process_started() {
        $this->resetAfterTest(true);
        $options = array();
        $options['data'] = tool_downloaddata_processor::DATA_USERS;
        $options['format'] = tool_downloaddata_processor::FORMAT_XLS;
        $options['delimiter'] = 'comma';
        $options['encoding'] = 'UTF-8';
        $options['roles'] = 'student,editingteacher';
        $options['useoverwrites'] = true;
        $options['sortbycategorypath'] = true;
        $processor = new tool_downloaddata_processor($options);
        $processor->execute();

        $this->setExpectedException('coding_exception', get_string('processstarted', 'tool_downloaddata'));
        $processor->execute();
    }
}
