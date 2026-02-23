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
require_once($CFG->libdir . '/formslib.php');

class feedback_response_form extends \moodleform {
    protected function definition() {
        $mform = $this->_form;

        $mform->addElement('editor', 'teacher_response_html_editor', '', [
            'rows' => 15,
        ], [
            'context' => $this->_customdata['context'],
            'autosave' => false,
        ]);
        $mform->setType('teacher_response_html_editor', PARAM_RAW);

        $buttonarray = [];
        $buttonarray[] = $mform->createElement('submit', 'save', get_string('savechanges'));
        $buttonarray[] = $mform->createElement('submit', 'submitfeedback', get_string('submit_to_user', 'exaaifeedback'));
        $buttonarray[] = $mform->createElement('cancel');
        $mform->addGroup($buttonarray, 'buttonar', '', ' ', false);
    }

    function display() {
        echo '<style>
            .mform.full-width-form .fcontainer { max-width: 100%; }
            .mform.full-width-form .felement { margin-left: 0; }
            .mform.full-width-form .col-md-3 { display: none; }
            .mform.full-width-form .col-md-9 { flex: 0 0 100%; max-width: 100%; }
        </style>';
        $this->_form->_attributes['class'] .= ' full-width-form';
        parent::display();
        ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                function replaceInputWithButton(id, icon) {
                    var input = document.getElementById(id);
                    if (!input) return;
                    input.outerHTML = input.outerHTML.replace(/^<input/, '<button').replace(/>$/, '>') + '<i class="fa ' + icon + '"></i> ' + input.value + '</button>';
                }
                replaceInputWithButton('id_save', 'fa-save');
                replaceInputWithButton('id_submitfeedback', 'fa-paper-plane');
            });
        </script>
        <?php
    }
}

$id = required_param('id', PARAM_INT);
$completedid = required_param('completedid', PARAM_INT);

$cm = get_coursemodule_from_id('exaaifeedback', $id, 0, true, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$instance = $DB->get_record('exaaifeedback', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);

$context = context_module::instance($cm->id);
\mod_exaaifeedback\permissions::require_manage($context);

$PAGE->set_url('/mod/exaaifeedback/feedback_edit.php', ['id' => $cm->id, 'completedid' => $completedid]);
$PAGE->set_title(format_string($instance->name));
$PAGE->set_heading(format_string($course->fullname));

// Get result record.
$result_record = feedback::get_result($instance->id, $completedid);

// Don't allow editing if already released.
if ($result_record->timefeedbacksent ?? false) {
    redirect(new moodle_url('/mod/exaaifeedback/feedback_details.php', ['id' => $cm->id, 'completedid' => $completedid]));
}

$return_url = new moodle_url('/mod/exaaifeedback/feedback_details.php', ['id' => $cm->id, 'completedid' => $completedid]);

$form = new feedback_response_form($PAGE->url, ['context' => $context]);

if ($form->is_cancelled()) {
    redirect($return_url);
}

if ($data = $form->get_data()) {
    $teacher_response_html = $data->teacher_response_html_editor['text'];
    feedback::update_teacher_response_html($instance->id, $completedid, $teacher_response_html);

    if (!empty($data->submitfeedback)) {
        feedback::mark_as_submitted($instance->id, $completedid);
        redirect(
            new moodle_url('/mod/exaaifeedback/feedbacks.php', ['id' => $cm->id]),
            get_string('feedback_submitted', 'exaaifeedback'),
            null,
            \core\output\notification::NOTIFY_SUCCESS,
        );
    } else {
        redirect(
            $return_url,
            get_string('changes_saved', 'exaaifeedback'),
            null,
            \core\output\notification::NOTIFY_SUCCESS,
        );
    }
}

$form->set_data([
    'teacher_response_html_editor' => [
        'text' => $result_record->data->final_response_html,
        'format' => FORMAT_HTML,
    ],
]);

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('edit_feedback', 'exaaifeedback'), 2);
$form->display();

echo $OUTPUT->footer();
