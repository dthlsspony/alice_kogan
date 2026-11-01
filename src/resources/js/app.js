// Frontend JavaScript
document.addEventListener('DOMContentLoaded', function () {
    // Auto-submit search form on tag change (debounced)
    // Works on both home page and search results page
    const searchForms = document.querySelectorAll('form[action*="/search"]');
    
    searchForms.forEach(function (form) {
        let debounceTimer;
        const tagCheckboxes = form.querySelectorAll('input[name="tags[]"]');
        
        tagCheckboxes.forEach(function (checkbox) {
            checkbox.addEventListener('change', function () {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(function () {
                    form.submit();
                }, 300);
            });
        });
    });
});

// Мобильное меню (гамбургер)
document.addEventListener('DOMContentLoaded', function () {
    const burger = document.getElementById('k-burger');
    const menu = document.getElementById('k-menu');

    if (burger && menu) {
        burger.addEventListener('click', function () {
            const open = menu.classList.toggle('k-open');
            burger.setAttribute('aria-expanded', open ? 'true' : 'false');
        });

        document.addEventListener('click', function (e) {
            if (menu.classList.contains('k-open') && !menu.contains(e.target) && !burger.contains(e.target)) {
                menu.classList.remove('k-open');
                burger.setAttribute('aria-expanded', 'false');
            }
        });
    }
});
