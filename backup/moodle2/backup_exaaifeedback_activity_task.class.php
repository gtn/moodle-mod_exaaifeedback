<?php

require_once($CFG->dirroot . '/mod/exaaifeedback/backup/moodle2/backup_exaaifeedback_stepslib.php');

class backup_exaaifeedback_activity_task extends backup_activity_task {

    protected function define_my_settings() {
    }

    protected function define_my_steps() {
        $this->add_step(new backup_exaaifeedback_activity_structure_step('exaaifeedback_structure', 'exaaifeedback.xml'));
    }

    public static function encode_content_links($content) {
        global $CFG;

        $base = preg_quote($CFG->wwwroot, '!');

        $search = "!({$base}/mod/exaaifeedback/index\.php\?id=)(\d+)!";
        $content = preg_replace($search, '$@EXAAIFEEDBACKINDEX*$2@$', $content);

        $search = "!({$base}/mod/exaaifeedback/view\.php\?id=)(\d+)!";
        $content = preg_replace($search, '$@EXAAIFEEDBACKVIEWBYID*$2@$', $content);

        return $content;
    }
}
