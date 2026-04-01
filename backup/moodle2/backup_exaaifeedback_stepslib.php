<?php

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
