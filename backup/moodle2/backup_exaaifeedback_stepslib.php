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

class backup_exaaifeedback_activity_structure_step extends backup_activity_structure_step {

    protected function define_structure() {
        $userinfo = $this->get_setting_value('userinfo');

        $exaaifeedback = new backup_nested_element('exaaifeedback', ['id'], [
            'name',
            'feedbackid',
            'prompt',
            'intro',
            'introformat',
            'timemodified',
        ]);

        $results = new backup_nested_element('results');
        $result = new backup_nested_element('result', ['id'], [
            'completedid',
            'timefeedbacksent',
            'timecreated',
            'timemodified',
            'data',
        ]);

        $exaaifeedback->add_child($results);
        $results->add_child($result);

        $exaaifeedback->set_source_table('exaaifeedback', ['id' => backup::VAR_ACTIVITYID]);

        if ($userinfo) {
            $result->set_source_table('exaaifeedback_result', ['exaaifeedbackid' => backup::VAR_PARENTID]);
        }

        return $this->prepare_activity_structure($exaaifeedback);
    }
}
