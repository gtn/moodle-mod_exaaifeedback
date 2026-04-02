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

use mod_exaaifeedback\feedback;
use mod_exaaifeedback\output;
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

// Generate on first access, or get cached result with needs_regeneration flag.
$error = '';
try {
    $result_data = feedback::generate_ai_feedback($instance, $course->id, $completedid, $completed);
} catch (\Exception $e) {
    $error = $e->getMessage();
}

if ($error) {
    echo $OUTPUT->header();
    echo $OUTPUT->notification($error, 'error');
    echo $OUTPUT->footer();
    exit;
}

$result = feedback::get_result($instance->id, $completedid);
$answers = $result_data->answers;

// Regenerate AI feedback.
if ($action === 'regenerate') {
    require_sesskey();

    try {
        feedback::regenerate_ai_feedback($instance, $course->id, $completedid, $completed);
    } catch (\Exception $e) {
        redirect(
            new moodle_url('/mod/exaaifeedback/feedback_details.php', ['id' => $cm->id, 'completedid' => $completedid]),
            $e->getMessage(),
            null,
            \core\output\notification::NOTIFY_ERROR,
        );
    }

    redirect(
        new moodle_url('/mod/exaaifeedback/feedback_details.php', ['id' => $cm->id, 'completedid' => $completedid]),
    );
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
    $final_response_html = $result->data->final_response_html;

    printer::generate_pdf(
        $instance->name,
        $instance->intro,
        $answers,
        $final_response_html,
        $username,
    );
    exit;
}

$PAGE->requires->js_call_amd('mod_exaaifeedback/main', 'initFeedbackDetails');

echo $OUTPUT->header();

echo $OUTPUT->render_from_template('mod_exaaifeedback/feedback_details_info', [
    'username' => $username,
    'date' => userdate($completed->timemodified),
    'timefeedbacksent' => (bool)$result->timefeedbacksent,
    'submitted_label' => get_string('submitted', 'exaaifeedback'),
    'submitted_date' => $result->timefeedbacksent ? userdate($result->timefeedbacksent) : '',
]);

$buttons = [];

$buttons[] = [
    'url' => (new moodle_url('/mod/exaaifeedback/feedback_details.php', [
        'id' => $cm->id,
        'completedid' => $completedid,
        'action' => 'pdf',
    ]))->out(false),
    'class' => 'btn-secondary',
    'icon' => 'fa-file-pdf-o',
    'label' => get_string('print_feedback', 'exaaifeedback'),
    'target' => '_blank',
];

if ($result->timefeedbacksent) {
    $buttons[] = [
        'url' => (new moodle_url('/mod/exaaifeedback/feedback_details.php', [
            'id' => $cm->id,
            'completedid' => $completedid,
            'action' => 'withdraw',
            'sesskey' => sesskey(),
        ]))->out(false),
        'class' => 'btn-warning',
        'icon' => 'fa-undo',
        'label' => get_string('withdraw_feedback', 'exaaifeedback'),
    ];
} else {
    $buttons[] = [
        'url' => (new moodle_url('/mod/exaaifeedback/feedback_edit.php', [
            'id' => $cm->id,
            'completedid' => $completedid,
        ]))->out(false),
        'class' => 'btn-secondary',
        'icon' => 'fa-edit',
        'label' => get_string('edit_feedback', 'exaaifeedback'),
    ];
    $buttons[] = [
        'url' => (new moodle_url('/mod/exaaifeedback/feedback_details.php', [
            'id' => $cm->id,
            'completedid' => $completedid,
            'action' => 'regenerate',
            'sesskey' => sesskey(),
        ]))->out(false),
        'class' => 'btn-secondary',
        'icon' => 'fa-refresh',
        'label' => get_string('regenerate_feedback', 'exaaifeedback'),
        'data-action' => 'regenerate',
    ];
    $buttons[] = [
        'url' => (new moodle_url('/mod/exaaifeedback/feedback_details.php', [
            'id' => $cm->id,
            'completedid' => $completedid,
            'action' => 'submit',
            'sesskey' => sesskey(),
        ]))->out(false),
        'class' => 'btn-primary',
        'icon' => 'fa-paper-plane',
        'label' => get_string('submit_to_user', 'exaaifeedback'),
    ];
}

$buttons_data = ['buttons' => $buttons];

if (!$result->timefeedbacksent && $result_data->needs_regeneration) {
    $buttons_data['regeneration_notice'] = $OUTPUT->notification(
        get_string('feedback_can_be_regenerated', 'exaaifeedback'),
        'info',
    );
}

$buttons_html = $OUTPUT->render_from_template('mod_exaaifeedback/feedback_details_buttons', $buttons_data);

echo $buttons_html;

output::feedback_details($answers, $result->data->final_response_html, $instance, $cm->id);

echo $buttons_html;

echo $OUTPUT->footer();
