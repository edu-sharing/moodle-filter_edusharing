import {eventTypes} from 'core_filters/events';
import {markObserved, observableElements} from './observation';

/**
 * The placeholder this filter leaves behind for every widget.
 *
 * @type {string}
 */
const PLACEHOLDER_SELECTOR = '.edusharing-widget-placeholder';

const widgetAttributeWhitelist = [
    'context-node-id',
    'widget-type',
    'node-id',
    'propagated-node-id',
    'config-overwrite',
    'search-text'
];

const allowedCustomElements = [
    'wlo-content-teaser'
];

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
 * @param {Element} element
 */
const renderWidget = (element) => {
    const previewImage = element.firstChild;
    const data = previewImage.getAttribute('data-widget');
    const decodedData = JSON.parse(data);
    if (!allowedCustomElements.includes(decodedData.tag)) {
        return;
    }
    const attrs = decodedData.attrs ?? {};
    const widget = document.createElement(decodedData.tag);
    Object.entries(attrs).forEach(([name, value]) => {
        if (!widgetAttributeWhitelist.includes(name)) {
            return;
        }
        if (value === null || value === undefined || value === false) {
            return;
        }
        if (value === true) {
            widget.setAttribute(name, '');
            return;
        }
        widget.setAttribute(name, String(value));
    });
    element.replaceWith(widget);
};

const observerCallback = (entries, currentObserver) => {
    for (const entry of entries) {
        currentObserver.unobserve(entry.target);
        try {
            renderWidget(entry.target);
        } catch (error) {
            // one broken widget must not stop the remaining ones of this batch from rendering
            window.console.error(error);
        }
    }
};

/**
 * Hands every widget placeholder below the given root that is not being rendered already to
 * the observer.
 *
 * @param {Document|Element} root
 * @returns {void}
 */
const observeWidgetsWithin = (root) => {
    observableElements(root, PLACEHOLDER_SELECTOR).forEach(element => {
        markObserved(element);
        observer.observe(element);
    });
};

/**
 * Replaces every widget placeholder of the page with its web component once it comes into view.
 *
 * Course formats that fetch their content by ajax - format_tiles for one - replace the markup
 * of a whole section long after this has run, so the placeholders of that section have to be
 * picked up again when core_filters/contentUpdated announces the exchange. Repeated calls only
 * add what is new. See filter_edusharing/edu for the same handling of inline objects.
 */
export const init = () => {
    if (observer === null) {
        observer = new IntersectionObserver(observerCallback, OBSERVER_OPTIONS);
        document.addEventListener(eventTypes.filterContentUpdated, event => {
            const nodes = event.detail?.nodes ?? [];
            Array.prototype.forEach.call(nodes, node => observeWidgetsWithin(node));
        });
    }
    observeWidgetsWithin(document);
};
