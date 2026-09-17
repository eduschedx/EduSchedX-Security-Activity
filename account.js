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

const verifySavedActivity = async () => {
    try {
        const response = await fetch('student-session-status.php', {
            credentials: 'same-origin',
            cache: 'no-store',
            headers: { Accept: 'application/json' },
        });
        if (response.status === 401 || response.status === 409) {
            sessionStorage.removeItem(studentTabKey);
            window.location.replace('login.php');
        }
    } catch (_) {
        // A temporary network failure must not interrupt an active activity.
    }
};
window.setInterval(verifySavedActivity, 3000);

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
