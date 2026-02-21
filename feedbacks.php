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

use mod_exaaifeedback\feedback;

require_once(__DIR__ . '/inc.php');

$id = required_param('id', PARAM_INT);

$cm = get_coursemodule_from_id('exaaifeedback', $id, 0, true, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$instance = $DB->get_record('exaaifeedback', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);

$context = context_module::instance($cm->id);
\mod_exaaifeedback\permissions::require_manage($context);

$PAGE->set_url('/mod/exaaifeedback/feedbacks.php', ['id' => $cm->id]);
$PAGE->set_title(format_string($instance->name));
$PAGE->set_heading(format_string($course->fullname));

$table = new class($instance->feedbackid, $cm->id, $instance->id) extends \local_table_sql\table_sql {
    public function __construct(protected int $feedbackid, protected int $cmid, protected int $exaaifeedbackid) {
        parent::__construct([$feedbackid, $exaaifeedbackid]);
    }

    protected function define_table_configs() {
        global $DB;

        $this->set_sql_query(
            "SELECT fc.id, fc.userid, fc.timemodified, fc.anonymous_response,
                " . $DB->sql_fullname('u.firstname', 'u.lastname') . " AS fullname,
                COALESCE(exaaifeedback_result.timefeedbacksent, 0) AS timefeedbacksent
            FROM {feedback_completed} fc
            LEFT JOIN {user} u ON u.id = fc.userid
            LEFT JOIN {exaaifeedback_result} exaaifeedback_result ON exaaifeedback_result.completedid = fc.id AND exaaifeedback_result.exaaifeedbackid = ?
            WHERE fc.feedback = ?",
            [$this->exaaifeedbackid, $this->feedbackid],
        );

        $this->set_table_columns([
            'fullname' => get_string('name'),
            'timemodified' => get_string('date'),
            'timefeedbacksent' => get_string('submitted', 'exaaifeedback'),
        ]);
        $this->set_column_options('timemodified', data_type: static::PARAM_TIMESTAMP);
        $this->set_column_options('timefeedbacksent', data_type: static::PARAM_TIMESTAMP);
        $this->sortable(true, 'timemodified', SORT_DESC);

        $this->add_row_action(
            url: new \moodle_url('/mod/exaaifeedback/feedback_details.php', ['id' => $this->cmid, 'completedid' => '{id}']),
            label: get_string('open_feedback', 'exaaifeedback'),
        );
    }

    public function col_fullname($row) {
        if ($row->anonymous_response == 1) {
            return get_string('anonymous', 'feedback');
        }
        return $row->fullname;
    }
};

echo $OUTPUT->header();

// echo format_module_intro('exaaifeedback', $instance, $cm->id);

\mod_exaaifeedback\output::tabtree($cm->id, 'overview');

$table->out();

echo $OUTPUT->footer();
