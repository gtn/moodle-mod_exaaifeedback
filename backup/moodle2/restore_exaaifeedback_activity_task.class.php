<?php

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
