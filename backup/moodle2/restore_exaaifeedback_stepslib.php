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

class restore_exaaifeedback_activity_structure_step extends restore_activity_structure_step {

    protected function define_structure() {
        $userinfo = $this->get_setting_value('userinfo');

        $paths = [];
        $paths[] = new restore_path_element('exaaifeedback', '/activity/exaaifeedback');

        if ($userinfo) {
            $paths[] = new restore_path_element('exaaifeedback_result', '/activity/exaaifeedback/results/result');
        }

        return $this->prepare_activity_structure($paths);
    }

    protected function process_exaaifeedback(array $data) {
        global $DB;

        $data = (object)$data;
        $data->course = $this->get_courseid();
        $data->feedbackid = $this->get_mappingid('feedback', $data->feedbackid) ?: 0;
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        $newitemid = $DB->insert_record('exaaifeedback', $data);
        $this->apply_activity_instance($newitemid);
    }

    protected function process_exaaifeedback_result(array $data) {
        global $DB;

        $data = (object)$data;
        $data->exaaifeedbackid = $this->get_new_parentid('exaaifeedback');
        $data->timefeedbacksent = $this->apply_date_offset($data->timefeedbacksent);
        $data->timecreated = $this->apply_date_offset($data->timecreated);
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        $DB->insert_record('exaaifeedback_result', $data);
    }

    protected function after_execute() {
        $this->add_related_files('mod_exaaifeedback', 'intro', null);
    }
}
