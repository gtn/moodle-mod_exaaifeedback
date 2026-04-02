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

class output {
    static function tabtree(int $cmid, string $selected): void {
        global $OUTPUT;

        $tabs = [
            new \tabobject('overview', new \moodle_url('/mod/exaaifeedback/feedbacks.php', ['id' => $cmid]), get_string('feedback_overview', 'exaaifeedback')),
            new \tabobject('content', new \moodle_url('/mod/exaaifeedback/moodle_feedback_details.php', ['id' => $cmid]), get_string('feedback_content', 'exaaifeedback')),
        ];

        echo $OUTPUT->tabtree($tabs, $selected);
    }

    static function feedback_details(array $answers, string $response_html, object $instance, int $cmid): void {
        global $OUTPUT;

        $show_answers = (bool)get_config('mod_exaaifeedback', 'show_answers');

        echo $OUTPUT->render_from_template('mod_exaaifeedback/feedback_content', [
            'has_intro' => (bool)$instance->intro,
            'intro_html' => $instance->intro ? format_module_intro('exaaifeedback', $instance, $cmid, false) : '',
            'answers_html' => $show_answers ? static::feedback_answers($answers) : '',
            'answers_heading' => get_string('feedback_answers', 'exaaifeedback'),
            'response_heading' => $instance->name,
            'response_html' => $response_html,
        ]);
    }

    static function feedback_answers(array $answers, bool $for_pdf = false): string {
        global $OUTPUT;

        $sections = static::group_answers_into_sections($answers);
        $template = $for_pdf ? 'mod_exaaifeedback/feedback_answers_pdf' : 'mod_exaaifeedback/feedback_answers';

        return $OUTPUT->render_from_template($template, ['sections' => $sections]);
    }

    private static function group_answers_into_sections(array $answers): array {
        $sections = [];
        $current_rows = [];

        foreach ($answers as $answer) {
            if (($answer->type ?? '') === 'label') {
                if ($current_rows) {
                    $sections[] = ['has_rows' => true, 'rows' => $current_rows];
                    $current_rows = [];
                }
                $sections[] = ['label' => format_text($answer->text, FORMAT_HTML)];
            } else {
                $current_rows[] = [
                    'question' => $answer->question,
                    'answer' => $answer->answer,
                ];
            }
        }

        if ($current_rows) {
            $sections[] = ['has_rows' => true, 'rows' => $current_rows];
        }

        return $sections;
    }
}
