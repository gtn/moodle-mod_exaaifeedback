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

defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) {
    $settings->add(new admin_setting_configstoredfile(
        'mod_exaaifeedback/logo',
        get_string('logo', 'exaaifeedback'),
        get_string('logo:desc', 'exaaifeedback'),
        'logo',
        0,
        ['accepted_types' => ['.png', '.jpg', '.jpeg', '.svg']],
    ));

    $settings->add(new admin_setting_configcheckbox(
        'mod_exaaifeedback/show_answers',
        get_string('show_answers', 'exaaifeedback'),
        get_string('show_answers:desc', 'exaaifeedback'),
        1,
    ));

    $settings->add(new admin_setting_configcheckbox(
        'mod_exaaifeedback/notify_user_on_release',
        get_string('notify_user_on_release', 'exaaifeedback'),
        get_string('notify_user_on_release:desc', 'exaaifeedback'),
        0,
    ));

    $settings->add(new admin_setting_configtext(
        'mod_exaaifeedback/pdf_font',
        get_string('pdf_font', 'exaaifeedback'),
        get_string('pdf_font:desc', 'exaaifeedback'),
        '',
    ));
}
