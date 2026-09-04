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
 * Shared bookkeeping for the lazy rendering of inline objects and widgets.
 *
 * @copyright  metaVentis GmbH — http://metaventis.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Marks a placeholder as handed to an observer.
 *
 * It is set on the live node only and never rendered by the server, which is exactly what is
 * wanted: a placeholder that arrives with freshly loaded markup is picked up again, while one
 * whose rendering has already been started - and which the observer has therefore stopped
 * watching - is not started a second time by a rescan.
 *
 * @type {string}
 */
const OBSERVED_ATTRIBUTE = 'data-edusharing-observed';

/**
 * @param {Element} element
 * @returns {void}
 */
export const markObserved = (element) => element.setAttribute(OBSERVED_ATTRIBUTE, '');

/**
 * Every placeholder below the given root that no observer has taken yet.
 *
 * The root is looked at as well, because a container that a course format has just refilled
 * can be the placeholder itself and querySelectorAll never returns its own root.
 *
 * @param {Document|Element} root
 * @param {string} selector
 * @returns {Element[]}
 */
export const observableElements = (root, selector) => {
    if (!root || typeof root.querySelectorAll !== 'function') {
        return [];
    }
    const candidates = Array.from(root.querySelectorAll(selector));
    if (typeof root.matches === 'function' && root.matches(selector)) {
        candidates.unshift(root);
    }
    return candidates.filter(element => !element.hasAttribute(OBSERVED_ATTRIBUTE));
};

/**
 * Calls back whenever elements matching the selector appear in the page.
 *
 * core_filters/contentUpdated is the polite way for a course format to announce content it
 * loaded by ajax, but it cannot be relied on: format_tiles only started emitting it in a
 * recent release, and the builds in the field replace a whole section without a word. Watching
 * the DOM itself is what makes the lazy rendering work no matter who does the replacing.
 *
 * Mutations are coalesced into one callback per frame, and the cheap containsMatch() test keeps
 * the common case - a mutation that has nothing to do with us - down to a single querySelector.
 *
 * @param {string} selector
 * @param {Function} onAdded run once per frame in which matching elements appeared
 * @returns {MutationObserver}
 */
export const watchForAddedElements = (selector, onAdded) => {
    let scheduled = false;
    const schedule = () => {
        if (scheduled) {
            return;
        }
        scheduled = true;
        window.requestAnimationFrame(() => {
            scheduled = false;
            onAdded();
        });
    };
    const containsMatch = (node) => node.nodeType === Node.ELEMENT_NODE
        && (node.matches(selector) || node.querySelector(selector) !== null);
    const mutationObserver = new MutationObserver(records => {
        for (const record of records) {
            for (const node of record.addedNodes) {
                if (containsMatch(node)) {
                    schedule();
                    return;
                }
            }
        }
    });
    mutationObserver.observe(document.body, {childList: true, subtree: true});
    return mutationObserver;
};
