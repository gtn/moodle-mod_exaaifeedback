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
        'mod_exaaifeedback/pdf_include_answers',
        get_string('pdf_include_answers', 'exaaifeedback'),
        get_string('pdf_include_answers:desc', 'exaaifeedback'),
        1,
    ));
}
