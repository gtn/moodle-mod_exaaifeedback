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
 * @package    mod_exaaifeedback
 * @copyright  2026 GTN Solutions https://gtn-solutions.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot . '/mod/exaaifeedback/backup/moodle2/restore_exaaifeedback_stepslib.php');

class restore_exaaifeedback_activity_task extends restore_activity_task {

    protected function define_my_settings() {
    }

    protected function define_my_steps() {
        $this->add_step(new restore_exaaifeedback_activity_structure_step('exaaifeedback_structure', 'exaaifeedback.xml'));
    }

    public static function define_decode_contents() {
        $contents = [];

        $contents[] = new restore_decode_content('exaaifeedback', ['intro'], 'exaaifeedback');

        return $contents;
    }

    public static function define_decode_rules() {
        $rules = [];

        $rules[] = new restore_decode_rule('EXAAIFEEDBACKINDEX', '/mod/exaaifeedback/index.php?id=$1', 'course');
        $rules[] = new restore_decode_rule('EXAAIFEEDBACKVIEWBYID', '/mod/exaaifeedback/view.php?id=$1', 'course_module');

        return $rules;
    }
}
