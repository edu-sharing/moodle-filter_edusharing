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

/**
 * Renders every inline edu-sharing object of the page once it comes into view.
 *
 * @param {string} repoUrl
 * @param {boolean} useServiceWorker
 */
export const start = (repoUrl, useServiceWorker) => {
    const allEduSharingObjects = document.querySelectorAll("div[data-type='esObject']");

    const options = {
        root: null,
        rootMargin: "400px",
        threshold: 0
    };

    const observerCallback = async(entries, observer) => {
        for (const entry of entries) {
            // an observer reports every target once on registration, whether it is on screen or
            // not - without this the 400px margin above would have no effect at all
            if (!entry.isIntersecting) {
                continue;
            }
            // unobserve up front so a further intersection cannot start a second render of the
            // same object while this one is still awaiting its secured node
            observer.unobserve(entry.target);
            try {
                await renderObject(entry.target, repoUrl, useServiceWorker);
            } catch (error) {
                // one broken object must not stop the remaining ones of this batch from rendering
                window.console.error(error);
            }
        }
    };

    const observer = new IntersectionObserver(observerCallback, options);
    allEduSharingObjects.forEach(element => observer.observe(element));
};
