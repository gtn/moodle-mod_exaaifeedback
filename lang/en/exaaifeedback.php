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

$string['modulename'] = 'Exabis AI Feedback';
$string['modulenameplural'] = 'Exabis AI Feedbacks';
$string['pluginname'] = 'Exabis AI Feedback';
$string['pluginadministration'] = 'Exabis AI Feedback administration';
$string['privacy:metadata:exaaifeedback_result'] = 'Stores AI-generated feedback results linked to user feedback submissions.';
$string['privacy:metadata:exaaifeedback_result:completedid'] = 'The ID of the completed feedback submission.';
$string['privacy:metadata:exaaifeedback_result:timefeedbacksent'] = 'The time the feedback was released to the user.';
$string['privacy:metadata:exaaifeedback_result:timecreated'] = 'The time the AI feedback was generated.';
$string['privacy:metadata:exaaifeedback_result:timemodified'] = 'The time the AI feedback was last modified.';
$string['privacy:metadata:exaaifeedback_result:data'] = 'JSON data containing the AI-generated feedback, user answers, and teacher edits.';
$string['exaaifeedback:addinstance'] = 'Add a new Exabis AI Feedback';
$string['exaaifeedback:view'] = 'View own AI Feedback';
$string['exaaifeedback:manage'] = 'Manage AI Feedbacks';
$string['feedback'] = 'Feedback activity';
$string['question'] = 'Question';
$string['answer'] = 'Answer';
$string['feedback_overview'] = 'Submissions';
$string['feedback_content'] = 'View questionnaire';
$string['position'] = 'Position';
$string['type'] = 'Type';
$string['prompt'] = 'AI Prompt';
$string['prompt:desc'] = '';
$string['open_feedback'] = 'View Feedback';
$string['ai_feedback'] = 'Feedback';
$string['feedback_answers'] = 'Your Answers';
$string['print_feedback'] = 'Print feedback';
$string['submit_to_user'] = 'Release to user';
$string['feedback_submitted'] = 'Feedback released';
$string['submitted'] = 'Released on';
$string['withdraw_feedback'] = 'Revoke release';
$string['feedback_withdrawn'] = 'Feedback withdrawn';
$string['no_feedback_submitted'] = 'You have not submitted a feedback yet.';
$string['feedback_not_yet_available'] = 'Your feedback is not yet available.';
$string['changes_saved'] = 'Changes saved';
$string['edit_feedback'] = 'Edit feedback';
$string['logo'] = 'Logo';
$string['logo:desc'] = 'Logo displayed on the feedback PDF.';
$string['show_answers'] = 'Show answers to users';
$string['show_answers:desc'] = 'If enabled, the questions and answers are shown to users in the feedback view and PDF.';
$string['pdf_font'] = 'PDF font';
$string['pdf_font:desc'] = 'Google Font name for PDF output (e.g., Roboto, Open Sans). Leave empty for default Arial.';
$string['notify_user_on_release'] = 'Notify user on release';
$string['notify_user_on_release:desc'] = 'If enabled, users receive a notification when their feedback is released.';
$string['notification:feedback_released:subject'] = 'Your feedback for "{$a}" is available';
$string['notification:feedback_released:body'] = 'Your feedback for "{$a}" has been released and is now available for you to view.';
$string['messageprovider:feedback_released'] = 'Feedback released notification';
$string['regenerate_feedback'] = 'Regenerate AI feedback';
$string['regenerate_feedback_confirm'] = 'Are you sure? The current feedback and any edits will be replaced.';
$string['feedback_can_be_regenerated'] = 'The prompt or answers have changed since the last generation. You can regenerate the AI feedback.';
