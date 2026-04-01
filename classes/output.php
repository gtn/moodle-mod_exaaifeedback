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

        if ($instance->intro) {
            echo '<div style="text-align: justify; margin-top: 2rem;">' . format_module_intro('exaaifeedback', $instance, $cmid, false) . '</div>';
        }

        if (get_config('mod_exaaifeedback', 'show_answers')) {
            echo $OUTPUT->heading(get_string('feedback_answers', 'exaaifeedback'), 2);
            echo static::feedback_answers($answers);
            echo $OUTPUT->heading($instance->name, 2);
        }

        echo '<div class="ai-response">' . $response_html . '</div>';
    }

    static function feedback_answers(array $answers, bool $for_pdf = false): string {
        ob_start();

        $in_table = false;
        $table_start = '<table class="generaltable" style="table-layout: fixed;">
            <colgroup><col style="width: 50%"><col style="width: 50%"></colgroup>';

        foreach ($answers as $answer) {
            if (($answer->type ?? '') === 'label') {
                if ($in_table) {
                    echo '</table>';
                    $in_table = false;
                }

                if ($for_pdf) {
                    ?>
                    <div style="margin-left: 10px; font-weight: bold;">
                        <?php echo format_text($answer->text, FORMAT_HTML) ?>
                    </div>
                    <?php
                } else {
                    ?>
                    <h3 style="margin-left: 12px"><?php echo format_text($answer->text, FORMAT_HTML) ?></h3>
                    <?php
                }
            } else {
                if (!$in_table) {
                    echo $table_start;
                    $in_table = true;
                }
                ?>
                <tr>
                    <th><?php echo s($answer->question) ?></th>
                    <td><?php echo s($answer->answer) ?></td>
                </tr>
                <?php
            }
        }

        if ($in_table) {
            echo '</table>';
        }

        return ob_get_clean();
    }
}
