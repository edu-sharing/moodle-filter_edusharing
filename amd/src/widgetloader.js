export const init = (repourl, hasnewrenderer) => {
    if (!hasnewrenderer) {
        window.__env = {
            EDU_SHARING_API_URL: `${repourl}/rest`
        };
    }
    // Helper to add scripts
    /**
     * @param {string} src
     * @param {string} type
     */
    function loadScript(src, type) {
        const script = document.createElement('script');
        script.src = src;
        if (type) {
            script.type = type;
        }
        script.async = false;
        document.head.appendChild(script);
    }

    /**
     * Function to add CSS to the page.
     * @param {string} href
     */
    function loadCSS(href) {
        const link = document.createElement('link');
        link.rel = 'stylesheet';
        link.type = 'text/css';
        link.href = href;
        document.head.appendChild(link);
    }

// Add polyfills
    loadScript(repourl + '/web-components/app/polyfills.js', 'module');

// Add the main module
    loadScript(repourl + '/web-components/app/main.js', 'module');

// Add styles
    loadCSS(repourl + '/web-components/app/styles.css');
};
