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

require_once(__DIR__ . '/inc.php');

$id = required_param('id', PARAM_INT);

$cm = get_coursemodule_from_id('exaaifeedback', $id, 0, true, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$instance = $DB->get_record('exaaifeedback', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);

$context = context_module::instance($cm->id);
\mod_exaaifeedback\permissions::require_manage($context);

$PAGE->set_url('/mod/exaaifeedback/moodle_feedback_details.php', ['id' => $cm->id]);
$PAGE->set_title(format_string($instance->name));
$PAGE->set_heading(format_string($course->fullname));

$table = new class($instance->feedbackid) extends \local_table_sql\table_sql {
    public function __construct(protected int $feedbackid) {
        parent::__construct([$feedbackid]);
    }

    protected function define_table_configs() {
        $this->set_sql_query(
            "SELECT fi.id, fi.name, fi.typ, fi.position, fi.required, fi.presentation
            FROM {feedback_item} fi
            WHERE fi.feedback = ?
            ORDER BY fi.position",
            [$this->feedbackid],
        );

        $this->set_table_columns([
            'position' => get_string('position', 'exaaifeedback'),
            'name' => get_string('question', 'exaaifeedback'),
            'typ' => get_string('type', 'exaaifeedback'),
            'required' => get_string('required'),
        ]);

        $this->sortable(true, 'position', SORT_ASC);
    }

    public function col_name($row) {
        if ($row->typ === 'label') {
            return format_text($row->presentation, FORMAT_HTML);
        }
        if ($row->typ === 'pagebreak') {
            return '-';
        }
        return s($row->name);
    }

    public function col_required($row) {
        if ($row->typ === 'label' || $row->typ === 'pagebreak') {
            return '-';
        }
        return $row->required ? get_string('yes') : get_string('no');
    }

    public function wrap_html_start() {
        ?>
        <style>
            .local_table_sql-column-name p {
                /* needed for label type questions to remove extra spacing */
                margin-bottom: 0;
            }
        </style>
        <?php
    }
};

echo $OUTPUT->header();

\mod_exaaifeedback\output::tabtree($cm->id, 'content');

$table->out();

echo $OUTPUT->footer();
