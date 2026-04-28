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

require_once($CFG->dirroot . '/course/moodleform_mod.php');

class mod_exaaifeedback_mod_form extends moodleform_mod {
    public function definition() {
        global $DB;

        $mform = $this->_form;

        $mform->addElement('header', 'general', get_string('general'));

        $mform->addElement('text', 'name', get_string('name'), ['size' => '64']);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');

        $this->standard_intro_elements();

        $feedbacks = $DB->get_records('feedback', ['course' => $this->get_course()->id], 'name', 'id, name');
        $options = ['' => '=== ' . get_string('choose') . ' ==='];
        foreach ($feedbacks as $feedback) {
            $options[$feedback->id] = $feedback->name;
        }
        $mform->addElement('select', 'feedbackid', get_string('feedback', 'exaaifeedback'), $options);
        $mform->addRule('feedbackid', null, 'required', null, 'client');

        $mform->addElement('textarea', 'prompt', get_string('prompt', 'exaaifeedback'), ['rows' => 6, 'cols' => 64]);
        $mform->setType('prompt', PARAM_RAW);
        $mform->addElement('static', 'prompt_info', '', get_string('prompt:desc', 'exaaifeedback'));

        $mform->addElement('header', 'notification_header', get_string('notification_settings', 'exaaifeedback'));
        $mform->setExpanded('notification_header');

        $mform->addElement('advcheckbox', 'notification_custom', get_string('notification_custom', 'exaaifeedback'));
        $mform->addElement('static', 'notification_custom_info', '', get_string('notification_custom:desc', 'exaaifeedback'));

        $mform->addElement('text', 'notification_subject', get_string('notification_subject', 'exaaifeedback'), ['size' => '64']);
        $mform->setType('notification_subject', PARAM_TEXT);
        $mform->hideIf('notification_subject', 'notification_custom', 'notchecked');

        $mform->addElement('textarea', 'notification_body', get_string('notification_body', 'exaaifeedback'), ['rows' => 4, 'cols' => 64]);
        $mform->setType('notification_body', PARAM_TEXT);
        $mform->hideIf('notification_body', 'notification_custom', 'notchecked');

        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }
}
