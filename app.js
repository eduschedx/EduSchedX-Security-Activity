document.querySelectorAll('form').forEach((form) => {
    form.addEventListener('submit', (event) => {
        if (form.hasAttribute('data-final-submit') && form.dataset.confirmed !== 'true') {
            event.preventDefault();
            document.querySelector('[data-submit-dialog]')?.showModal();
            return;
        }
        const button = form.querySelector('[data-confirm-submit]')
            || document.querySelector(`[data-confirm-submit][form="${form.id}"]`);
        const runButton = form.querySelector('[data-run-code]');
        if (runButton) {
            runButton.disabled = true;
            runButton.innerHTML = '<span class="spinner-border spinner-border-sm" aria-hidden="true"></span> Running code...';
        }
        if (!button) {
            return;
        }
        button.disabled = true;
        button.textContent = 'Submitting...';
    });
});

const submitDialog = document.querySelector('[data-submit-dialog]');
const finalSubmitForm = document.querySelector('[data-final-submit]');
if (submitDialog && finalSubmitForm) {
    document.querySelectorAll('[data-submit-cancel]').forEach((button) => {
        button.addEventListener('click', () => submitDialog.close());
    });
    submitDialog.addEventListener('click', (event) => {
        if (event.target === submitDialog) submitDialog.close();
    });
    document.querySelector('[data-submit-confirm]')?.addEventListener('click', () => {
        finalSubmitForm.dataset.confirmed = 'true';
        submitDialog.close();
        finalSubmitForm.requestSubmit();
    });
}

document.querySelectorAll('[data-answer]').forEach((input) => {
    const target = document.querySelector(`[data-answer-target="${input.dataset.answer}"]`);
    const sync = () => {
        if (target) target.value = input.value;
    };
    input.addEventListener('input', sync);
    sync();
});

document.querySelectorAll('[data-activity-code]').forEach((input) => {
    input.addEventListener('input', () => {
        input.value = input.value.toUpperCase().replace(/[^A-HJ-NP-Z2-9]/g, '').slice(0, 6);
    });
});

document.querySelectorAll('[data-student-id]').forEach((input) => {
    input.addEventListener('input', () => {
        input.value = input.value.toUpperCase().replace(/[^A-Z0-9-]/g, '').slice(0, 10);
    });
});

document.querySelectorAll('[data-auto-dismiss]').forEach((alert) => {
    const delay = Number.parseInt(alert.dataset.autoDismiss, 10);
    if (Number.isFinite(delay) && delay > 0) {
        window.setTimeout(() => alert.remove(), delay);
    }
});

if (document.querySelector('[data-activity-page]')) {
    document.addEventListener('contextmenu', (event) => event.preventDefault());
    document.querySelectorAll('[data-code-answer]').forEach((input) => {
        ['copy', 'cut', 'paste', 'drop', 'dragstart'].forEach((eventName) => {
            input.addEventListener(eventName, (event) => event.preventDefault());
        });
    });
    document.querySelectorAll('[data-no-copy]').forEach((area) => {
        ['copy', 'cut', 'paste', 'drop', 'dragstart'].forEach((eventName) => {
            area.addEventListener(eventName, (event) => event.preventDefault());
        });
    });
    document.addEventListener('keydown', (event) => {
        const key = event.key.toLowerCase();
        if (event.key === 'Tab' || event.key === 'F12' ||
            ((event.ctrlKey || event.metaKey) && ['c', 'v', 'x', 'u'].includes(key)) ||
            (event.ctrlKey && event.shiftKey && ['i', 'j'].includes(key))) {
            event.preventDefault();
        }
    });
}
