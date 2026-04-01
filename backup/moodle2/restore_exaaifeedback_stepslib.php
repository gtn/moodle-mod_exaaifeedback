<?php

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
