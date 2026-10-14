(function () {
    'use strict';
    var STORAGE_KEY = 'theme';
    var root = document.documentElement;

    function getStored() {
        try {
            return localStorage.getItem(STORAGE_KEY);
        } catch (e) {
            return null;
        }
    }

    function apply(theme) {
        if (theme !== 'dark' && theme !== 'light') {
            theme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        }
        root.setAttribute('data-theme', theme);
        try {
            localStorage.setItem(STORAGE_KEY, theme);
        } catch (e) { /* ignore */ }
    }

    var initial = getStored();
    if (initial === 'dark' || initial === 'light') {
        apply(initial);
    } else {
        apply(root.getAttribute('data-theme') || 'light');
    }

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.theme-toggle');
        if (!btn) return;
        e.preventDefault();
        var cur = root.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';
        apply(cur === 'dark' ? 'light' : 'dark');
    });
})();
