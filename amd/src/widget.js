export const init = () => {
    const allWidgets = document.querySelectorAll('.edusharing-widget-placeholder');

    const options = {
        root: null,
        rootMargin: "400px",
        threshold: 0
    };

    const observerCallback = (entries, observer) => {
        for (const entry of entries) {
            renderWidget(entry.target);
            observer.unobserve(entry.target);
        }
    };
    /**
     * @param {Element} element
     */
    const renderWidget = (element) => {
        const previewImage = element.firstChild;
        const data = previewImage.getAttribute('data-widget');
        const decodedData = JSON.parse(data);
        const attrs = decodedData.attrs ?? {};
        const widget = document.createElement(decodedData.tag);
        Object.entries(attrs).forEach(([name, value]) => {
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
    const observer = new IntersectionObserver(observerCallback, options);
    allWidgets.forEach(element => observer.observe(element));
};
