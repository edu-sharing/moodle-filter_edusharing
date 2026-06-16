// This file is part of edu-sharing created by metaVentis GmbH — http://metaventis.com
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @copyright  metaVentis GmbH — http://metaventis.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {renderObject} from 'mod_edusharing/renderer';

export const start = (repoUrl) => {
    const allEduSharingObjects = document.querySelectorAll("div[data-type='esObject']");

    const options = {
        root: null,
        rootMargin: "400px",
        threshold: 0
    };

    const observerCallback = async(entries, observer) => {
        for (const entry of entries) {
            await renderObject(entry.target, repoUrl);
            observer.unobserve(entry.target);
        }
    };

    const observer = new IntersectionObserver(observerCallback, options);
    allEduSharingObjects.forEach(element => observer.observe(element));
};
