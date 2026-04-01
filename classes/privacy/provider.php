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

namespace mod_exaaifeedback\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

defined('MOODLE_INTERNAL') || die();

class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\core_userlist_provider {

    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('exaaifeedback_result', [
            'completedid' => 'privacy:metadata:exaaifeedback_result:completedid',
            'timefeedbacksent' => 'privacy:metadata:exaaifeedback_result:timefeedbacksent',
            'timecreated' => 'privacy:metadata:exaaifeedback_result:timecreated',
            'timemodified' => 'privacy:metadata:exaaifeedback_result:timemodified',
            'data' => 'privacy:metadata:exaaifeedback_result:data',
        ], 'privacy:metadata:exaaifeedback_result');

        return $collection;
    }

    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();

        $sql = "SELECT ctx.id
                  FROM {context} ctx
                  JOIN {course_modules} cm ON cm.id = ctx.instanceid AND ctx.contextlevel = :contextlevel
                  JOIN {modules} m ON m.id = cm.module AND m.name = 'exaaifeedback'
                  JOIN {exaaifeedback} e ON e.id = cm.instance
                  JOIN {exaaifeedback_result} er ON er.exaaifeedbackid = e.id
                  JOIN {feedback_completed} fc ON fc.id = er.completedid
                 WHERE fc.userid = :userid";

        $contextlist->add_from_sql($sql, [
            'contextlevel' => CONTEXT_MODULE,
            'userid' => $userid,
        ]);

        return $contextlist;
    }

    public static function get_users_in_context(userlist $userlist): void {
        $context = $userlist->get_context();

        if (!$context instanceof \context_module) {
            return;
        }

        $sql = "SELECT fc.userid
                  FROM {exaaifeedback_result} er
                  JOIN {exaaifeedback} e ON e.id = er.exaaifeedbackid
                  JOIN {course_modules} cm ON cm.instance = e.id
                  JOIN {modules} m ON m.id = cm.module AND m.name = 'exaaifeedback'
                  JOIN {feedback_completed} fc ON fc.id = er.completedid
                 WHERE cm.id = :cmid";

        $userlist->add_from_sql('userid', $sql, ['cmid' => $context->instanceid]);
    }

    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \context_module) {
                continue;
            }

            $sql = "SELECT er.*
                      FROM {exaaifeedback_result} er
                      JOIN {exaaifeedback} e ON e.id = er.exaaifeedbackid
                      JOIN {course_modules} cm ON cm.instance = e.id
                      JOIN {modules} m ON m.id = cm.module AND m.name = 'exaaifeedback'
                      JOIN {feedback_completed} fc ON fc.id = er.completedid
                     WHERE cm.id = :cmid AND fc.userid = :userid";

            $results = $DB->get_records_sql($sql, [
                'cmid' => $context->instanceid,
                'userid' => $userid,
            ]);

            foreach ($results as $result) {
                $data = (object)[
                    'timefeedbacksent' => $result->timefeedbacksent
                        ? \core_privacy\local\request\transform::datetime($result->timefeedbacksent) : null,
                    'timecreated' => \core_privacy\local\request\transform::datetime($result->timecreated),
                    'timemodified' => \core_privacy\local\request\transform::datetime($result->timemodified),
                    'data' => $result->data,
                ];

                writer::with_context($context)->export_data(
                    [get_string('pluginname', 'mod_exaaifeedback'), $result->id],
                    $data,
                );
            }
        }
    }

    public static function delete_data_for_all_users_in_context(\context $context): void {
        global $DB;

        if (!$context instanceof \context_module) {
            return;
        }

        $cm = get_coursemodule_from_id('exaaifeedback', $context->instanceid);
        if (!$cm) {
            return;
        }

        $DB->delete_records('exaaifeedback_result', ['exaaifeedbackid' => $cm->instance]);
    }

    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        global $DB;

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \context_module) {
                continue;
            }

            $cm = get_coursemodule_from_id('exaaifeedback', $context->instanceid);
            if (!$cm) {
                continue;
            }

            $sql = "SELECT er.id
                      FROM {exaaifeedback_result} er
                      JOIN {feedback_completed} fc ON fc.id = er.completedid
                     WHERE er.exaaifeedbackid = :exaaifeedbackid AND fc.userid = :userid";

            $ids = $DB->get_fieldset_sql($sql, [
                'exaaifeedbackid' => $cm->instance,
                'userid' => $userid,
            ]);

            if ($ids) {
                $DB->delete_records_list('exaaifeedback_result', 'id', $ids);
            }
        }
    }

    public static function delete_data_for_users(approved_userlist $userlist): void {
        global $DB;

        $context = $userlist->get_context();

        if (!$context instanceof \context_module) {
            return;
        }

        $cm = get_coursemodule_from_id('exaaifeedback', $context->instanceid);
        if (!$cm) {
            return;
        }

        $userids = $userlist->get_userids();
        if (!$userids) {
            return;
        }

        [$insql, $inparams] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);

        $sql = "SELECT er.id
                  FROM {exaaifeedback_result} er
                  JOIN {feedback_completed} fc ON fc.id = er.completedid
                 WHERE er.exaaifeedbackid = :exaaifeedbackid AND fc.userid {$insql}";

        $ids = $DB->get_fieldset_sql($sql, ['exaaifeedbackid' => $cm->instance] + $inparams);

        if ($ids) {
            $DB->delete_records_list('exaaifeedback_result', 'id', $ids);
        }
    }
}
