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
use mod_exaaifeedback\printer;

require_once(__DIR__ . '/inc.php');

$id = required_param('id', PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);

$cm = get_coursemodule_from_id('exaaifeedback', $id, 0, true, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$instance = $DB->get_record('exaaifeedback', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);

$context = context_module::instance($cm->id);

// Teachers/managers get redirected to feedbacks.php.
if (\mod_exaaifeedback\permissions::can_manage($context)) {
    redirect(new moodle_url('/mod/exaaifeedback/feedbacks.php', ['id' => $cm->id]));
}

// Students need view capability.
\mod_exaaifeedback\permissions::require_view($context);

$PAGE->set_url('/mod/exaaifeedback/view.php', ['id' => $cm->id]);
$PAGE->set_title(format_string($instance->name));
$PAGE->set_heading(format_string($course->fullname));

// Get the user's completed feedback.
$completed = $DB->get_record('feedback_completed', [
    'feedback' => $instance->feedbackid,
    'userid' => $USER->id,
]);

if (!$completed) {
    echo $OUTPUT->header();
    echo $OUTPUT->notification(get_string('no_feedback_submitted', 'exaaifeedback'), 'info');
    echo $OUTPUT->footer();
    exit;
}

// Get feedback data if available and released.
$final_response_html = '';
$answers = [];
$result = feedback::get_result($instance->id, $completed->id);
if (($result->timefeedbacksent ?? 0) > 0) {
    $final_response_html = $result->data->final_response_html;
    $answers = $result->data->answers ?? [];
}

if (!$final_response_html) {
    echo $OUTPUT->header();
    echo $OUTPUT->notification(get_string('feedback_not_yet_available', 'exaaifeedback'), 'info');
    echo $OUTPUT->footer();
    exit;
}

// PDF download (before header output).
if ($action === 'pdf') {
    printer::generate_pdf(
        $instance->name,
        $answers,
        $final_response_html,
        fullname($USER),
    );
    exit;
}

echo $OUTPUT->header();

// not needed: boost union is printing the module intro in the header already, and if we do it here as well, it is printed twice.
// echo format_module_intro('exaaifeedback', $instance, $cm->id);

$render_buttons = function() use ($cm, $instance) {
    global $PAGE;
    $pdf_url = $PAGE->url->out(true, ['action' => 'pdf']);

    ?>
    <div style="margin: 15px 0;">
        <a href="<?= $pdf_url ?>" class="btn btn-primary" target="_blank">
            <i class="fa fa-file-pdf-o"></i>
            <?= get_string('print_feedback', 'exaaifeedback') ?>
        </a>
    </div>
    <?php
};

$render_buttons();

\mod_exaaifeedback\output::feedback_details($answers, $final_response_html);

$render_buttons();

echo $OUTPUT->footer();
