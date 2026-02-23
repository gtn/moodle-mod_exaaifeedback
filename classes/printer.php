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

use Dompdf\Dompdf;
use Dompdf\Options;

defined('MOODLE_INTERNAL') || die;

class printer {
    static function get_logo_data_uri(): string {
        $fs = get_file_storage();
        $context = \context_system::instance();
        $files = $fs->get_area_files($context->id, 'mod_exaaifeedback', 'logo', 0, 'sortorder', false);
        $file = reset($files);
        if (!$file) {
            return '';
        }

        $mimetype = $file->get_mimetype();
        $content = $file->get_content();

        return 'data:' . $mimetype . ';base64,' . base64_encode($content);
    }

    static function generate_pdf(string $title, string $description, array $answers, string $response_html, string $username): void {
        global $CFG;

        $html = static::get_html($title, $description, $answers, $response_html, $username);

        helper::composer_autoload();

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);

        if (!file_exists($CFG->tempdir . '/mod_exaaifeedback/dompdf_font_cache')) {
            mkdir($CFG->tempdir . '/mod_exaaifeedback/dompdf_font_cache', 0777, true);
        }
        $options->setFontDir($CFG->tempdir . '/mod_exaaifeedback/dompdf_font_cache');
        $options->setFontCache($CFG->tempdir . '/mod_exaaifeedback/dompdf_font_cache');

        $dompdf = new Dompdf($options);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->loadHtml($html);
        $dompdf->render();

        $canvas = $dompdf->getCanvas();
        $canvas->page_script(function($pageNumber, $pageCount, $canvas, $fontMetrics) use ($title, $username) {
            $font = $fontMetrics->get_font('Helvetica', 'normal');
            $canvas->text(40, 800, $title . ' - ' . $username, $font, 10);
            $pageText = $pageNumber . ' / ' . $pageCount;
            $canvas->text(510, 800, $pageText, $font, 10);
        });

        $dompdf->stream("AI-Feedback.pdf", ["Attachment" => false]);
    }

    static function get_html(string $title, string $description, array $answers, string $response_html, string $username): string {
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                @page {
                    margin: 14mm;
                }

                body {
                    font-family: Arial, sans-serif;
                    font-size: 12px;
                }

                h1 {
                    font-size: 18px;
                    color: #333;
                    margin-bottom: 5px;
                }

                h2 {
                    font-size: 14px;
                    color: #555;
                    margin-top: 20px;
                    margin-bottom: 10px;
                    border-bottom: 1px solid #ccc;
                    padding-bottom: 5px;
                }

                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-bottom: 20px;
                }

                th, td {
                    padding: 6px 10px;
                    border: 1px solid #dee2e6;
                    text-align: left;
                    vertical-align: top;
                }

                th, td {
                    width: 50%;
                }

                th {
                    background-color: #f5f5f5;
                    font-weight: bold;
                }

                .ai-response {
                    background-color: #f9f9f9;
                    border: 1px solid #ddd;
                    border-radius: 4px;
                    padding: 12px;
                    line-height: 1.5;
                }

                .subtitle {
                    color: #777;
                    font-size: 11px;
                    margin-bottom: 20px;
                }
            </style>
        </head>
        <body>

        <?php $logo = static::get_logo_data_uri(); ?>
        <?php if ($logo): ?>
            <div style="padding-left: 20px; float: right;">
                <img src="<?= $logo ?>" style="max-width: 200px; max-height: 200px;">
            </div>
        <?php endif; ?>

        <h1><?= htmlspecialchars($title) ?></h1>
        <?php if ($username): ?>
            <div class="subtitle"><?= htmlspecialchars($username) ?></div>
        <?php endif; ?>
        <?php if ($description): ?>
            <div style="margin-bottom: 15px;"><?= format_text($description, FORMAT_HTML) ?></div>
        <?php endif; ?>

        <h2><?= get_string('feedback_answers', 'exaaifeedback') ?></h2>
        <?= output::feedback_answers($answers, true) ?>

        <h2 style="page-break-before: always;"><?= get_string('ai_feedback', 'exaaifeedback') ?></h2>
        <div class="ai-response">
            <?= $response_html ?>
        </div>

        </body>
        </html>
        <?php
        return ob_get_clean();
    }
}
