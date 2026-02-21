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

class output {
    static function tabtree(int $cmid, string $selected): void {
        global $OUTPUT;

        $tabs = [
            new \tabobject('overview', new \moodle_url('/mod/exaaifeedback/feedbacks.php', ['id' => $cmid]), get_string('feedback_overview', 'exaaifeedback')),
            new \tabobject('content', new \moodle_url('/mod/exaaifeedback/moodle_feedback_details.php', ['id' => $cmid]), get_string('feedback_content', 'exaaifeedback')),
        ];

        echo $OUTPUT->tabtree($tabs, $selected);
    }

    static function feedback_details(array $answers, string $ai_response): void {
        global $OUTPUT;

        // Show the original feedback answers.
        echo $OUTPUT->heading(get_string('feedback_answers', 'exaaifeedback'), 2);
        ?>
        <table class="generaltable" style="table-layout: fixed;">
        <colgroup><col style="width: 50%"><col style="width: 50%"></colgroup>
        <?php foreach ($answers as $answer): ?>
            <?php if (($answer->type ?? '') === 'label'): ?>
                <tr class="label-row">
                    <td colspan="2"><?= format_text($answer->text, FORMAT_HTML) ?></td>
                </tr>
            <?php elseif (($answer->type ?? '') === 'pagebreak'): ?>
                <!-- don't show -->
            <?php else: ?>
                <tr>
                    <th><?= s($answer->question) ?></th>
                    <td><?= s($answer->answer) ?></td>
                </tr>
            <?php endif; ?>
        <?php endforeach; ?>
        </table>

        <?php
        // Show the AI response.
        echo $OUTPUT->heading(get_string('ai_feedback', 'exaaifeedback'), 2);
        echo $OUTPUT->box(format_text($ai_response, FORMAT_MARKDOWN), 'ai-feedback-response');
    }
}
