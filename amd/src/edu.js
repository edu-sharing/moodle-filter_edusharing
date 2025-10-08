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

import {getSecuredNode} from './repository';
import {getCurrentUser} from './repository';
import Config from 'core/config';

export const start = (repoUrl) => {
    const allEduSharingObjects = document.querySelectorAll("div[data-type='esObject']");

    const options = {
        root: null,
        rootMargin: "400px",
        threshold: 0
    };

    const observerCallback = async(entries, observer) => {
        for (const entry of entries) {
            await renderObject(entry.target);
            observer.unobserve(entry.target);
        }
    };

    /**
     * @param {Element} element
     */
    const renderObject = async(element) => {
        const wrapper = element.parentElement;
        const width = element.getAttribute('data-width');
        const nodeId = element.getAttribute('data-node');
        const containerId = element.getAttribute('data-container');
        const version = element.getAttribute('data-version');
        const usage = element.getAttribute('data-usage');
        const resourceId = element.getAttribute('data-resource');

        const resourceUrl = `${Config.wwwroot}/filter/edusharing/inlineHelper.php?` +
            `nodeId=${nodeId}&nodeVersion=${version}&usageId=${usage}&resourceId=${resourceId}&containerId=${containerId}`;

        const ajaxParams = {
            eduSecuredNodeStructure: {
                nodeId: nodeId,
                resourceId: resourceId,
            }
        };

        const response = await getSecuredNode(ajaxParams).catch(error => {
            window.console.error(error);
        });

        const customWidth = response.customWidth;
        if (customWidth) {
            if (customWidth !== 'none') {
                wrapper.style.width = customWidth;
            }
        } else {
            wrapper.style.width = width ? (width + "px") : '';
        }
        const moodleUser = await getCurrentUser().catch(error => {
            window.console.error(error);
        });

        const eduUser = {
            authorityName: moodleUser.username,
            firstName: moodleUser.firstname,
            surName: moodleUser.lastname,
            userEMail: moodleUser.email
        };
        const serviceWorkerPhp = `${Config.wwwroot}/filter/edusharing/getServiceWorker.php`;
        if ('serviceWorker' in navigator) {
            await navigator.serviceWorker.register(serviceWorkerPhp, {
                scope: '/'
            });
        }

        const renderComponent = document.createElement('edu-sharing-render');
        renderComponent.classList.add('edu-sharing-render');
        renderComponent.encoded_node = response.securedNode;
        renderComponent.signature = response.signature;
        renderComponent.jwt = response.jwt;
        renderComponent.render_url = response.renderingBaseUrl;
        renderComponent.encoded_user = btoa(JSON.stringify(eduUser));
        renderComponent.service_worker_url = serviceWorkerPhp;
        renderComponent.activate_service_worker = false;
        renderComponent.assets_url = repoUrl + '/web-components/rendering-service-amd/assets';
        renderComponent.resource_url = resourceUrl;
        renderComponent.preview_url = response.previewUrl;
        wrapper.innerHTML = "";
        wrapper.appendChild(renderComponent);
    };

    const observer = new IntersectionObserver(observerCallback, options);
    allEduSharingObjects.forEach(element => observer.observe(element));
};
