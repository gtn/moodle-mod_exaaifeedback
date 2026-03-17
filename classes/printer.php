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
        $options->set('isRemoteEnabled', true);

        $fontcachedir = make_cache_directory('mod_exaaifeedback/dompdf_font_cache');
        $options->setFontDir($fontcachedir);
        $options->setFontCache($fontcachedir);

        $dompdf = new Dompdf($options);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->loadHtml($html);
        $dompdf->render();

        $canvas = $dompdf->getCanvas();
        $canvas->page_script(function($pageNumber, $pageCount, $canvas, $fontMetrics) use ($title, $username) {
            $font = $fontMetrics->get_font('Helvetica', 'normal');
            $canvas->text(40, 800, $title . ' - ' . $username, $font, 10);
            $pageText = $pageNumber . ' / ' . $pageCount;
            $textWidth = $fontMetrics->get_text_width($pageText, $font, 10);
            $canvas->text(555 - $textWidth, 800, $pageText, $font, 10);
        });

        $filename = clean_filename($title . ' - ' . $username) . '.pdf';
        $dompdf->stream($filename, ["Attachment" => false]);
    }

    static function get_html(string $title, string $description, array $answers, string $response_html, string $username): string {
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <?php $pdf_font = get_config('mod_exaaifeedback', 'pdf_font'); ?>
            <?php if ($pdf_font): ?>
                <link href="https://fonts.googleapis.com/css2?family=<?php echo str_replace(' ', '+', $pdf_font) ?>:wght@400;700&display=swap" rel="stylesheet">
            <?php endif; ?>
            <style>
                @page {
                    margin: 14mm;
                }

                body {
                    font-family: <?php echo $pdf_font ? "'" . htmlspecialchars($pdf_font) . "', " : '' ?>Arial, sans-serif;
                    font-size: 12px;
                    line-height: <?php echo strtolower($pdf_font) === 'figtree' ? '1.3' : '1.5' ?>;
                }

                h1 {
                    font-size: 18px;
                }

                h2 {
                    font-size: 16px;
                }

                h3 {
                    font-size: 14px;
                }

                h2.with-line {
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
                    text-align: justify;
                }

                .subtitle {
                    color: #555;
                    margin-bottom: 20px;
                }
            </style>
        </head>
        <body>

        <?php $logo = static::get_logo_data_uri(); ?>
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="vertical-align: bottom; border: none; padding: 0;">
                    <h1 style="margin: 0 0 5px 0;"><?php echo htmlspecialchars($title) ?></h1>
                    <?php if ($username): ?>
                        <div class="subtitle"><?php echo htmlspecialchars($username) ?></div>
                    <?php endif; ?>
                </td>
                <?php if ($logo): ?>
                    <td style="width: 4cm; vertical-align: top; text-align: right; border: none; padding: 0; padding-left: 5mm;">
                        <img src="<?php echo $logo ?>" style="max-width: 4cm; max-height: 6cm;">
                    </td>
                <?php endif; ?>
            </tr>
        </table>

        <?php if ($description): ?>
            <div style="margin-top: 0; text-align: justify;"><?php echo format_text($description, FORMAT_HTML) ?></div>
        <?php endif; ?>

        <?php if (get_config('mod_exaaifeedback', 'show_answers')): ?>
            <h2 class="with-line"><?php echo get_string('feedback_answers', 'exaaifeedback') ?></h2>
            <?php echo output::feedback_answers($answers, true) ?>

            <h2 class="with-line" style="page-break-before: always;"><?php echo htmlspecialchars($title) ?></h2>
            <div class="ai-response">
                <?php echo $response_html ?>
            </div>
        <?php else: ?>
            <div class="ai-response">
                <?php echo $response_html ?>
            </div>
        <?php endif; ?>

        </body>
        </html>
        <?php
        return ob_get_clean();
    }
}
