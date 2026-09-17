const logoutDialog = document.querySelector('[data-logout-dialog]');
const logoutForm = document.getElementById('student-logout-form');
const studentTabKey = 'eduschedx_tab_authenticated';

if (sessionStorage.getItem(studentTabKey) !== '1') {
    window.location.replace('login.php?tab_required=1');
} else {
    document.documentElement.classList.remove('student-auth-pending');
}

window.addEventListener('pageshow', (event) => {
    if (event.persisted) window.location.reload();
});

if (logoutDialog && logoutForm) {
    document.querySelector('[data-logout-open]')?.addEventListener('click', () => {
        document.querySelector('.student-account')?.removeAttribute('open');
        logoutDialog.showModal();
    });
    document.querySelectorAll('[data-logout-cancel]').forEach((button) => {
        button.addEventListener('click', () => logoutDialog.close());
    });
    logoutDialog.addEventListener('click', (event) => {
        if (event.target === logoutDialog) logoutDialog.close();
    });
    document.querySelector('[data-logout-confirm]')?.addEventListener('click', (event) => {
        event.currentTarget.disabled = true;
        event.currentTarget.innerHTML = '<span class="spinner-border spinner-border-sm" aria-hidden="true"></span> Logging out...';
        logoutForm.requestSubmit();
    });
}
