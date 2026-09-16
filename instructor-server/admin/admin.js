const deleteModal = document.getElementById('deleteModal');

if (deleteModal) {
    deleteModal.addEventListener('show.bs.modal', (event) => {
        const trigger = event.relatedTarget;
        const input = deleteModal.querySelector('[name="submission_id"]');
        input.value = trigger?.dataset.submissionId ?? '';
    });
}

document.querySelectorAll('[data-auto-dismiss]').forEach((alert) => {
    const delay = Number.parseInt(alert.dataset.autoDismiss, 10);
    if (Number.isFinite(delay) && delay > 0) {
        window.setTimeout(() => alert.remove(), delay);
    }
});
