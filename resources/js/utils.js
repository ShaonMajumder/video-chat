// resources/js/utils.js
(function () {
    const isProduction = document.querySelector('meta[name="app-env"]')?.content === 'production';

    window.log = (...args) => {
        if (!isProduction) {
            console.log(...args);
        }
    };
})();