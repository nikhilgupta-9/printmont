// Settings script fallback
(function() {
    function showBody() {
        if (document.body) {
            document.body.style.opacity = '1';
        }
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', showBody);
    } else {
        showBody();
    }
})();
