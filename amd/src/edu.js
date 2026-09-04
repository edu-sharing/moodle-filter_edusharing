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
import {eventTypes} from 'core_filters/events';
import {markObserved, observableElements, watchForAddedElements} from './observation';

/**
 * The placeholder this filter leaves behind for every inline object.
 *
 * @type {string}
 */
const OBJECT_SELECTOR = "div[data-type='esObject']";

const OBSERVER_OPTIONS = {
    root: null,
    rootMargin: "400px",
    threshold: 0
};

/**
 * @type {IntersectionObserver|null}
 */
let observer = null;

/**
 * @type {string}
 */
let repositoryUrl = '';

/**
 * @type {boolean}
 */
let serviceWorkerEnabled = false;

/**
 * @param {IntersectionObserverEntry[]} entries
 * @param {IntersectionObserver} currentObserver
 * @returns {Promise<void>}
 */
const observerCallback = async(entries, currentObserver) => {
    for (const entry of entries) {
        // an observer reports every target once on registration, whether it is on screen or
        // not - without this the 400px margin above would have no effect at all
        if (!entry.isIntersecting) {
            continue;
        }
        // unobserve up front so a further intersection cannot start a second render of the
        // same object while this one is still awaiting its secured node
        currentObserver.unobserve(entry.target);
        try {
            await renderObject(entry.target, repositoryUrl, serviceWorkerEnabled);
        } catch (error) {
            // one broken object must not stop the remaining ones of this batch from rendering
            window.console.error(error);
        }
    }
};

/**
 * Hands every inline object below the given root that is not being rendered already to the
 * observer.
 *
 * @param {Document|Element} root
 * @returns {void}
 */
const observeObjectsWithin = (root) => {
    observableElements(root, OBJECT_SELECTOR).forEach(element => {
        markObserved(element);
        observer.observe(element);
    });
};

/**
 * Renders every inline edu-sharing object of the page once it comes into view.
 *
 * Course formats that fetch their content by ajax - format_tiles for one - throw the markup of
 * a whole section away and replace it with a freshly filtered copy, long after this has run.
 * That copy carries new placeholders which nothing observes, so its objects would keep
 * spinning; core_filters/contentUpdated is what such a format announces the exchange with, and
 * is therefore what picks the new placeholders up. Repeated calls only add what is new, so it
 * does not matter how often a format re-runs this.
 *
 * @param {string} repoUrl
 * @param {boolean} useServiceWorker
 */
export const start = (repoUrl, useServiceWorker) => {
    repositoryUrl = repoUrl;
    serviceWorkerEnabled = useServiceWorker;
    if (observer === null) {
        observer = new IntersectionObserver(observerCallback, OBSERVER_OPTIONS);
        document.addEventListener(eventTypes.filterContentUpdated, event => {
            const nodes = event.detail?.nodes ?? [];
            Array.prototype.forEach.call(nodes, node => observeObjectsWithin(node));
        });
        // The event above is not emitted by every course format - see watchForAddedElements.
        watchForAddedElements(OBJECT_SELECTOR, () => observeObjectsWithin(document));
    }
    observeObjectsWithin(document);
};
