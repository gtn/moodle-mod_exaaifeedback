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
$completedid = required_param('completedid', PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);

$cm = get_coursemodule_from_id('exaaifeedback', $id, 0, true, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$instance = $DB->get_record('exaaifeedback', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);

$context = context_module::instance($cm->id);
\mod_exaaifeedback\permissions::require_manage($context);

$PAGE->set_url('/mod/exaaifeedback/feedback_details.php', ['id' => $cm->id, 'completedid' => $completedid]);
$PAGE->set_title(format_string($instance->name));
$PAGE->set_heading(format_string($course->fullname));

// Verify the completed feedback exists and belongs to this feedback.
$completed = $DB->get_record('feedback_completed', ['id' => $completedid, 'feedback' => $instance->feedbackid], '*', MUST_EXIST);

// Resolve username for display.
$username = '';
if ($completed->anonymous_response != 1 && $completed->userid) {
    $user = $DB->get_record('user', ['id' => $completed->userid]);
    $username = $user ? fullname($user) : '';
} else {
    $username = get_string('anonymous', 'feedback');
}

// Generate AI feedback.
$error = '';
try {
    $result = feedback::generate_ai_feedback($instance, $course->id, $completedid, $completed);
    $answers = $result->answers;
    $ai_response = $result->ai_response;
} catch (\Exception $e) {
    $error = $e->getMessage();
    $answers = [];
    $ai_response = '';
}

if ($error) {
    echo $OUTPUT->header();
    echo $OUTPUT->notification($error, 'error');
    echo $OUTPUT->footer();
    exit;
}

// Submit feedback to user.
if ($action === 'submit') {
    require_sesskey();
    feedback::mark_as_submitted($instance->id, $completedid);
    redirect(
        new moodle_url('/mod/exaaifeedback/feedbacks.php', ['id' => $cm->id]),
        get_string('feedback_submitted', 'exaaifeedback'),
        null,
        \core\output\notification::NOTIFY_SUCCESS,
    );
}

// Withdraw feedback.
if ($action === 'withdraw') {
    require_sesskey();
    feedback::withdraw($instance->id, $completedid);
    redirect(
        new moodle_url('/mod/exaaifeedback/feedback_details.php', ['id' => $cm->id, 'completedid' => $completedid]),
        get_string('feedback_withdrawn', 'exaaifeedback'),
        null,
        \core\output\notification::NOTIFY_SUCCESS,
    );
}

// PDF download.
if ($action === 'pdf') {
    printer::generate_pdf(
        $instance->name,
        $answers,
        $ai_response,
        $username,
    );
    exit;
}

echo $OUTPUT->header();

$result_record = feedback::get_result($instance->id, $completedid);

$render_buttons = function() use ($cm, $completedid, $result_record, $instance) {
    $pdf_url = new moodle_url('/mod/exaaifeedback/feedback_details.php', [
        'id' => $cm->id,
        'completedid' => $completedid,
        'action' => 'pdf',
    ]);
    $submit_url = new moodle_url('/mod/exaaifeedback/feedback_details.php', [
        'id' => $cm->id,
        'completedid' => $completedid,
        'action' => 'submit',
        'sesskey' => sesskey(),
    ]);
    $withdraw_url = new moodle_url('/mod/exaaifeedback/feedback_details.php', [
        'id' => $cm->id,
        'completedid' => $completedid,
        'action' => 'withdraw',
        'sesskey' => sesskey(),
    ]);

    ?>
    <div style="display: flex; gap: 8px; margin: 15px 0">
        <a href="<?= $pdf_url ?>" class="btn btn-secondary" target="_blank">
            <i class="fa fa-file-pdf-o"></i>
            <?= get_string('print_feedback', 'exaaifeedback', $instance->name) ?>
        </a>
        <?php if ($result_record->timefeedbacksent ?? false): ?>
            <a href="<?= $withdraw_url ?>" class="btn btn-warning">
                <i class="fa fa-undo"></i>
                <?= get_string('withdraw_feedback', 'exaaifeedback') ?>
            </a>
        <?php else: ?>
            <a href="<?= $submit_url ?>" class="btn btn-primary">
                <i class="fa fa-paper-plane"></i>
                <?= get_string('submit_to_user', 'exaaifeedback') ?>
            </a>
        <?php endif; ?>
    </div>
    <?php
};

$render_buttons();

\mod_exaaifeedback\output::feedback_details($answers, $ai_response);

$render_buttons();

echo $OUTPUT->footer();
