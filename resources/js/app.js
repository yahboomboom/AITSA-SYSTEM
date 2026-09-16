import './bootstrap';

function updateThemeIcon() {
    var icon = document.getElementById('theme-icon');
    if (icon) icon.className = document.documentElement.classList.contains('dark') ? 'fa-solid fa-sun text-sm' : 'fa-solid fa-moon text-sm';
}

function toggleTheme() {
    var isDark = document.documentElement.classList.toggle('dark');
    localStorage.setItem('theme', isDark ? 'dark' : 'light');
    updateThemeIcon();
}

window.toggleTheme = toggleTheme;
document.addEventListener('DOMContentLoaded', updateThemeIcon);
