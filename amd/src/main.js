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
 * @module     mod_exaaifeedback/main
 * @copyright  2026 GTN Solutions https://gtn-solutions.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {getString} from 'core/str';

/**
 * Replace submit inputs with styled buttons.
 * @param {string} id
 * @param {string} icon
 */
function replaceInputWithButton(id, icon) {
  var input = document.getElementById(id);
  if (!input) {
    return;
  }
  var button = document.createElement('button');
  button.type = input.type;
  button.name = input.name;
  button.value = input.value;
  button.className = input.className;
  button.id = input.id;
  button.innerHTML = '<i class="fa ' + icon + '"></i> ' + input.value;
  input.replaceWith(button);
}

export const initFeedbackEdit = () => {
  replaceInputWithButton('id_save', 'fa-save');
  replaceInputWithButton('id_submitfeedback', 'fa-paper-plane');
};

export const initFeedbackDetails = async () => {
  const confirmMessage = await getString('regenerate_feedback_confirm', 'mod_exaaifeedback');
  document.querySelectorAll('[data-action="regenerate"]').forEach((link) => {
    link.addEventListener('click', (e) => {
      if (!confirm(confirmMessage)) {
        e.preventDefault();
      }
    });
  });
};
