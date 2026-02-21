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

function exaaifeedback_add_instance(object $data): int {
    global $DB;

    $data->timemodified = time();

    return $DB->insert_record('exaaifeedback', $data);
}

function exaaifeedback_update_instance(object $data): bool {
    global $DB;

    $data->id = $data->instance;
    $data->timemodified = time();

    return $DB->update_record('exaaifeedback', $data);
}

function exaaifeedback_delete_instance(int $id): bool {
    global $DB;

    return $DB->delete_records('exaaifeedback', ['id' => $id]);
}

function exaaifeedback_cm_info_dynamic(cm_info $cm) {
    $context = context_module::instance($cm->id);
    // Hide activity if user has neither view nor manage capability.
    if (!\mod_exaaifeedback\permissions::can_view($context) && !\mod_exaaifeedback\permissions::can_manage($context)) {
        $cm->set_user_visible(false);
        $cm->set_available(false);
    }
}

function exaaifeedback_supports(string $feature): ?bool {
    return match ($feature) {
        FEATURE_MOD_INTRO => true,
        FEATURE_BACKUP_MOODLE2 => false,
        FEATURE_GRADE_HAS_GRADE => false,
        default => null,
    };
}
