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

namespace mod_exaaifeedback;

use block_exaaichat\completion\completion_base;

defined('MOODLE_INTERNAL') || die;

class feedback {
    const AI_LOGIC_VERSION = 2026022100;

    /**
     * Get all items (questions and labels) for a feedback.
     * @return object[] indexed by item id
     */
    static function get_items(int $feedbackid): array {
        global $DB;

        return $DB->get_records('feedback_item', [
            'feedback' => $feedbackid,
        ], 'position');
    }

    /**
     * Get all completed submissions with their values.
     * Returns an array of objects with: completedid, userid, timemodified, and a 'values' array (itemid => value).
     */
    static function get_completed_feedbacks(int $feedbackid): array {
        global $DB;

        $completeds = $DB->get_records('feedback_completed', ['feedback' => $feedbackid], 'timemodified DESC');
        $items = static::get_items($feedbackid);

        foreach ($completeds as $completed) {
            $values = $DB->get_records('feedback_value', ['completed' => $completed->id], '', 'item, value');
            $completed->values = [];
            foreach ($items as $item) {
                $completed->values[$item->id] = $values[$item->id]->value ?? '';
            }
        }

        return $completeds;
    }

    /**
     * Generate AI feedback for a completed feedback submission.
     */
    static function generate_ai_feedback(object $instance, int $courseid, int $completedid, object $completed): object {
        global $DB;

        $items = static::get_items($instance->feedbackid);
        $values = $DB->get_records('feedback_value', ['completed' => $completedid], '', 'item, value');

        // Build JSON representation of feedback answers to prevent prompt injection.
        $answers = [];
        foreach ($items as $item) {
            if ($item->typ === 'pagebreak') {
                continue;
            }

            if ($item->typ === 'label') {
                $entry = (object)[
                    'type' => $item->typ,
                    'text' => $item->presentation,
                ];
            } else {
                $entry = (object)[
                    'question' => $item->name,
                    'answer' => $values[$item->id]->value ?? '',
                ];

                // For multichoice: parse options from presentation field (format: r>>>>>opt1|opt2|opt3<<<<<1).
                if ($item->typ === 'multichoice' && preg_match('!>>>>>(.*?)<<<<<!', $item->presentation, $m)) {
                    $entry->options = array_map('trim', explode('|', $m[1]));
                }

                // For numeric: parse range from presentation field (format: min|max).
                if ($item->typ === 'numeric' && preg_match('!^(.+)\|(.+)$!', $item->presentation, $m)) {
                    $entry->range = (object)['min' => $m[1], 'max' => $m[2]];
                }
            }

            $answers[] = $entry;
        }

        $prompt = $instance->prompt ?: 'Please provide feedback for the following responses:';

        // Resolve user name for replacing in AI result.
        if ($completed->anonymous_response != 1 && $completed->userid) {
            $user = $DB->get_record('user', ['id' => $completed->userid]);
            $fullname = $user ? fullname($user) : '';
        } else {
            $fullname = get_string('anonymous', 'feedback');
        }

        // Check if a stored result already exists and is still valid.
        $check = md5(print_r([static::AI_LOGIC_VERSION, $prompt, $answers], true));
        $existing = static::get_result($instance->id, $completedid);
        $existing_data = json_decode($existing->data ?? '');

        // Don't regenerate if already submitted to user.
        if ($existing->timefeedbacksent ?? false) {
            $ret = $existing_data;
        } elseif (($existing_data->check ?? '') === $check) {
            $ret = $existing_data;
        } else {
            $system_message = $prompt;
            // Tell the AI about the {fullname} placeholder — it will be replaced after the AI ai_response.
            $system_message .= "\n\n" . 'Further instructions:
            You can use the placeholder {user.fullname} to refer to the user by name. It will be replaced with the actual name.
            Output raw Markdown without code blocks.
            ';

            $message = "The Answers from the questionaire are as follows (type label means a headline for a couple of questions): " .
                json_encode($answers, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

            // Get block_exaaichat config from course.
            $ai_config = completion_base::get_course_config($courseid);
            if (!$ai_config) {
                throw new \moodle_exception('No Exa AI Chat block found in this course.');
            }
            $ai_config->instructions = $system_message;
            $ai_config->sourceoftruth = '';

            $completion = completion_base::create_from_config($ai_config, $message, '', [], '');
            $result = $completion->create_completion();
            if ($result['error'] ?? '') {
                throw new \moodle_exception($result['error']);
            }

            $ai_response = $result['message'] ?? '';

            // check AI Response for sanity. Maybe the Token Limit was too low or no more funds available for the AI API.
            if (!$ai_response) {
                throw new \moodle_exception('No AI Response found.');
            }
            if (strlen($ai_response) < 50) {
                throw new \moodle_exception('AI Response to short');
            }

            // Replace {fullname} placeholder in the AI response with the actual name.
            $ai_response = str_replace('{user.fullname}', $fullname, $ai_response);

            $ret = (object)[
                'version' => static::AI_LOGIC_VERSION,
                'check' => $check,
                'time' => time(),
                'ai_response' => $ai_response,
                'answers' => $answers,
            ];

            // Save result to DB.
            $data = json_encode($ret);

            if ($existing) {
                $existing->data = $data;
                $existing->timemodified = time();
                $DB->update_record('exaaifeedback_result', $existing);
            } else {
                $DB->insert_record('exaaifeedback_result', (object)[
                    'exaaifeedbackid' => $instance->id,
                    'completedid' => $completedid,
                    'data' => $data,
                    'timefeedbacksent' => 0,
                    'timecreated' => time(),
                    'timemodified' => time(),
                ]);
            }
        }

        return $ret;
    }

    static function mark_as_submitted(int $exaaifeedbackid, int $completedid): void {
        global $DB;

        $DB->set_field('exaaifeedback_result', 'timefeedbacksent', time(), [
            'exaaifeedbackid' => $exaaifeedbackid,
            'completedid' => $completedid,
        ]);
    }

    static function withdraw(int $exaaifeedbackid, int $completedid): void {
        global $DB;

        $DB->set_field('exaaifeedback_result', 'timefeedbacksent', 0, [
            'exaaifeedbackid' => $exaaifeedbackid,
            'completedid' => $completedid,
        ]);
    }

    static function get_result(int $exaaifeedbackid, int $completedid): ?object {
        global $DB;

        return $DB->get_record('exaaifeedback_result', [
            'exaaifeedbackid' => $exaaifeedbackid,
            'completedid' => $completedid,
        ]) ?: null;
    }
}
