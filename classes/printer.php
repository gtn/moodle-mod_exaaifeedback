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
        global $OUTPUT;

        $pdf_font = get_config('mod_exaaifeedback', 'pdf_font');
        $show_answers = (bool)get_config('mod_exaaifeedback', 'show_answers');

        return $OUTPUT->render_from_template('mod_exaaifeedback/print_pdf', [
            'title' => $title,
            'username' => $username,
            'description' => $description ? format_text($description, FORMAT_HTML) : '',
            'logo' => static::get_logo_data_uri(),
            'answers_html' => $show_answers ? output::feedback_answers($answers, true) : '',
            'answers_heading' => get_string('feedback_answers', 'exaaifeedback'),
            'response_html' => $response_html,
            'font_url' => $pdf_font
                ? 'https://fonts.googleapis.com/css2?family=' . str_replace(' ', '+', $pdf_font) . ':wght@400;700&display=swap'
                : '',
            'font_family_css' => ($pdf_font ? "'" . $pdf_font . "', " : '') . 'Arial, sans-serif',
            'line_height' => strtolower($pdf_font ?: '') === 'figtree' ? '1.3' : '1.5',
        ]);
    }
}
