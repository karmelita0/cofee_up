document.addEventListener('DOMContentLoaded', () => {
    const authCard = document.getElementById('auth-card');
    const tabs = document.querySelectorAll('.auth-tab');
    const panels = document.querySelectorAll('.auth-panel');
    const switchLinks = document.querySelectorAll('.js-switch-tab');

    function activateAuthTab(target) {
        tabs.forEach(t => t.classList.toggle('active', t.dataset.tab === target));
        panels.forEach(p => p.classList.toggle('active', p.id === 'panel-' + target));
        if (authCard) {
            authCard.classList.toggle('auth-card-wide', target === 'register');
        }
    }

    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            activateAuthTab(tab.dataset.tab);
        });
    });

    switchLinks.forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            activateAuthTab(link.dataset.target);
        });
    });
});