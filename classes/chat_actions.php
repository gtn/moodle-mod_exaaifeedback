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

namespace mod_exaaifeedback;

defined('MOODLE_INTERNAL') || die;

/**
 * AI tool functions this plugin contributes to the Exa AI Chat block.
 *
 * Each public static method is offered to the AI assistant as a callable function.
 */
class chat_actions {
    /**
     * Get the AI-generated feedback the current user has received for the feedbacks of the current course.
     * @return array one entry per received feedback, each with the feedback name and content; empty if no feedback has been released to the user yet
     */
    public static function get_received_ai_feedback(): array {
        global $DB, $USER, $COURSE;

        $instances = $DB->get_records('exaaifeedback', ['course' => $COURSE->id]);

        $data = [];
        foreach ($instances as $instance) {
            if (!$instance->feedbackid) {
                continue;
            }

            $feedback = $DB->get_record('feedback', ['id' => $instance->feedbackid]);

            $completions = $DB->get_records('feedback_completed', [
                'feedback' => $instance->feedbackid,
                'userid' => $USER->id,
            ]);

            foreach ($completions as $completed) {
                $result = feedback::get_result($instance->id, $completed->id);

                // Only return feedback that has actually been released to the user.
                if (!($result && $result->timefeedbacksent)) {
                    continue;
                }

                $data[] = (object)[
                    'name' => $feedback->name,
                    'content' => trim(html_to_text($result->data->final_response_html ?? '')),
                ];
            }
        }

        return $data;
    }

    /**
     * Get the current user's most recent submissions to the feedbacks of the current course.
     * @param string $name optional: limit to the feedback with this exact name
     * @return array one entry per completed feedback, each with the feedback name, submission date and submitted answers; empty if the user has not submitted any feedback
     */
    public static function get_latest_feedback_submissions(string $name = ''): array {
        global $DB, $USER, $COURSE;

        $instances = $DB->get_records('exaaifeedback', ['course' => $COURSE->id]);

        $data = [];
        foreach ($instances as $instance) {
            if (!$instance->feedbackid) {
                continue;
            }

            $feedback = $DB->get_record('feedback', ['id' => $instance->feedbackid]);
            if (!$feedback) {
                continue;
            }
            if ($name && $feedback->name !== $name) {
                continue;
            }

            // Only the most recent completion of this feedback.
            $completed = $DB->get_records('feedback_completed', [
                'feedback' => $instance->feedbackid,
                'userid' => $USER->id,
            ], 'timemodified DESC', '*', 0, 1);
            $completed = reset($completed);

            if (!$completed) {
                continue;
            }

            $data[] = (object)[
                'name' => $feedback->name,
                'submitted' => userdate($completed->timemodified),
                'answers' => feedback::get_answers($instance->feedbackid, $completed->id),
            ];
        }

        return $data;
    }
}
