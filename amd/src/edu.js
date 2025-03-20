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

import {getSecuredNode} from "./repository";
import Config from 'core/config';

export const start = () => {
    const allEduSharingObjects = document.querySelectorAll("div[data-type='esObject']");

    const options = {
        root: null,
        rootMargin: "400px",
        threshold: 0
    };

    const observerCallback = async(entries, observer) => {
        for (const entry of entries) {
            window.console.log(entry.target);
            await renderObject(entry.target);
            observer.unobserve(entry.target);
        }
    };

    /**
     * @param {Element} element
     */
    const renderObject = async(element) => {
        const wrapper = element.parentElement;
        const nodeId = element.getAttribute('data-node');
        const ajaxParams = {
            eduSecuredNodeStructure: {
                nodeId: nodeId
            }
        };
        const response = await getSecuredNode(ajaxParams).catch(error => {
            window.console.error(error);
        });

        const testUser = {
            authorityName: "authorName",
            firstName: "Horst",
            surName: "Tester",
            userEMail: "mail@mail.de"
        };
        const serviceWorkerPhp = `${Config.wwwroot}/filter/edusharing/getServiceWorker.php`;
        const renderComponent = document.createElement('edu-sharing-render');
        renderComponent.setAttribute("encoded_node", response.securedNode);
        renderComponent.setAttribute("signature", response.signature);
        renderComponent.setAttribute("jwt", response.jwt);
        renderComponent.setAttribute("render_url", response.renderingBaseUrl);
        renderComponent.setAttribute("encoded_user", btoa(JSON.stringify(testUser)));
        renderComponent.setAttribute("service_worker_url", serviceWorkerPhp);
        wrapper.innerHTML = "";
        wrapper.appendChild(renderComponent);
    };

    const observer = new IntersectionObserver(observerCallback, options);
    allEduSharingObjects.forEach(element => observer.observe(element));
};
