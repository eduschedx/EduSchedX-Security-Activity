document.querySelectorAll('form').forEach((form) => {
    form.addEventListener('submit', (event) => {
        if (form.hasAttribute('data-final-submit') && form.dataset.confirmed !== 'true') {
            event.preventDefault();
            form.querySelector('[data-confirm-submit]')?.classList.add('is-confirming');
            document.querySelector('[data-submit-dialog]')?.showModal();
            return;
        }
        const codeAnswer = form.querySelector('[data-code-answer]')
            || document.querySelector(`[data-code-answer][form="${form.id}"]`);
        if (codeAnswer) codeAnswer.setCustomValidity('');
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
        button.addEventListener('click', () => {
            finalSubmitForm.querySelector('[data-confirm-submit]')?.classList.remove('is-confirming');
            submitDialog.close();
        });
    });
    submitDialog.addEventListener('click', (event) => {
        if (event.target === submitDialog) {
            finalSubmitForm.querySelector('[data-confirm-submit]')?.classList.remove('is-confirming');
            submitDialog.close();
        }
    });
    submitDialog.addEventListener('cancel', () => {
        finalSubmitForm.querySelector('[data-confirm-submit]')?.classList.remove('is-confirming');
    });
    document.querySelector('[data-submit-confirm]')?.addEventListener('click', () => {
        finalSubmitForm.dataset.confirmed = 'true';
        submitDialog.close();
        finalSubmitForm.requestSubmit();
    });
}

const partOneDialog = document.querySelector('[data-part-one-dialog]');
const partOneSubmitForm = document.querySelector('[data-part-one-submit]');
if (partOneDialog && partOneSubmitForm) {
    document.querySelector('[data-part-one-open]')?.addEventListener('click', () => partOneDialog.showModal());
    document.querySelectorAll('[data-part-one-cancel]').forEach((button) => {
        button.addEventListener('click', () => partOneDialog.close());
    });
    partOneDialog.addEventListener('click', (event) => {
        if (event.target === partOneDialog) partOneDialog.close();
    });
    document.querySelector('[data-part-one-confirm]')?.addEventListener('click', (event) => {
        event.currentTarget.disabled = true;
        event.currentTarget.textContent = 'Submitting...';
        partOneSubmitForm.requestSubmit();
    });
}

document.querySelectorAll('[data-auto-dialog]').forEach((dialog) => {
    if (typeof dialog.showModal === 'function' && !dialog.open) dialog.showModal();
});

const partThreeDialog = document.querySelector('[data-part-three-dialog]');
document.querySelector('[data-part-three-open]')?.addEventListener('click', () => partThreeDialog?.showModal());

const partThreeTimer = document.querySelector('[data-part-three-timer]');
const partThreeTimeoutForm = document.querySelector('[data-part-three-timeout]');
if (partThreeTimer && partThreeTimeoutForm) {
    let submitted = false;
    const deadline = Number(partThreeTimer.dataset.deadline) * 1000;
    const updateTimer = () => {
        const seconds = Math.max(0, Math.ceil((deadline - Date.now()) / 1000));
        const minutes = Math.floor(seconds / 60);
        partThreeTimer.textContent = `${String(minutes).padStart(2, '0')}:${String(seconds % 60).padStart(2, '0')}`;
        partThreeTimer.classList.toggle('is-ending', seconds > 0 && seconds <= 60);
        if (seconds === 0 && !submitted) {
            submitted = true;
            partThreeTimer.classList.add('is-expired');
            partThreeTimeoutForm.requestSubmit();
        }
    };
    updateTimer();
    if (!submitted) window.setInterval(updateTimer, 1000);
}

document.querySelectorAll('[data-unlock-form]').forEach((form) => {
    let selectedCard = null;
    const ordered = form.dataset.ordered === 'true';
    const cards = () => [...form.querySelectorAll('[data-unlock-card]')];
    if (form.dataset.locked === 'true') {
        cards().forEach((card) => {
            card.draggable = false;
            card.tabIndex = -1;
        });
        form.querySelectorAll('[data-unlock-target]').forEach((target) => {
            target.tabIndex = -1;
            target.removeAttribute('role');
        });
        form.querySelectorAll('[data-move]').forEach((button) => { button.disabled = true; });
        return;
    }
    const syncAssignments = () => {
        const checkButton = form.querySelector('[data-check-unlock]');
        if (ordered) {
            cards().forEach((card, index) => {
                card.querySelector('input').value = String(index + 1);
                const number = card.querySelector('.order-number');
                if (number) number.textContent = `${index + 1}`;
            });
            if (checkButton) checkButton.disabled = false;
            return;
        }
        cards().forEach((card) => {
            const target = card.closest('[data-unlock-target]');
            card.querySelector('input').value = target?.dataset.unlockTarget || '';
        });
        if (checkButton) checkButton.disabled = cards().some((card) => card.querySelector('input').value === '');
    };
    const selectCard = (card) => {
        cards().forEach((item) => item.classList.remove('is-selected'));
        selectedCard = card;
        card.classList.add('is-selected');
        form.querySelectorAll('[data-unlock-target]').forEach((target) => {
            target.classList.toggle('is-ready', !ordered || target.classList.contains('order-target'));
        });
    };
    const placeCard = (card, target) => {
        const destination = target.querySelector('.unlock-drop-cards') || target;
        destination.appendChild(card);
        card.classList.remove('is-selected');
        form.querySelectorAll('[data-unlock-target]').forEach((item) => item.classList.remove('is-ready', 'is-drag-over'));
        selectedCard = null;
        syncAssignments();
    };

    cards().forEach((card) => {
        card.addEventListener('click', (event) => {
            if (card.dataset.justDragged === 'true') {
                delete card.dataset.justDragged;
                event.preventDefault();
                return;
            }
            if (!event.target.closest('[data-move]')) selectCard(card);
        });
        card.addEventListener('dragstart', (event) => {
            selectCard(card);
            event.dataTransfer?.setData('text/plain', card.dataset.unlockCard);
        });
        let pointerDragging = false;
        let pointerStart = null;
        card.addEventListener('pointerdown', (event) => {
            if (event.button !== 0) return;
            pointerStart = { x: event.clientX, y: event.clientY };
            pointerDragging = false;
            card.setPointerCapture?.(event.pointerId);
            selectCard(card);
        });
        card.addEventListener('pointermove', (event) => {
            if (!pointerStart) return;
            if (Math.hypot(event.clientX - pointerStart.x, event.clientY - pointerStart.y) > 6) pointerDragging = true;
            if (!pointerDragging) return;
            form.querySelectorAll('[data-unlock-target]').forEach((item) => item.classList.remove('is-drag-over'));
            const target = document.elementFromPoint(event.clientX, event.clientY)?.closest('[data-unlock-target]');
            if (target && (!ordered || target.classList.contains('order-target'))) target.classList.add('is-drag-over');
        });
        card.addEventListener('pointerup', (event) => {
            if (pointerDragging) {
                card.dataset.justDragged = 'true';
                const target = document.elementFromPoint(event.clientX, event.clientY)?.closest('[data-unlock-target]');
                if (target && (!ordered || target.classList.contains('order-target'))) placeCard(card, target);
            }
            if (card.hasPointerCapture?.(event.pointerId)) card.releasePointerCapture(event.pointerId);
            pointerStart = null;
            pointerDragging = false;
        });
        card.addEventListener('pointercancel', () => {
            pointerStart = null;
            pointerDragging = false;
            form.querySelectorAll('[data-unlock-target]').forEach((item) => item.classList.remove('is-drag-over'));
        });
    });
    form.querySelectorAll('[data-unlock-target]').forEach((target) => {
        target.addEventListener('dragover', (event) => { event.preventDefault(); target.classList.add('is-drag-over'); });
        target.addEventListener('dragleave', () => target.classList.remove('is-drag-over'));
        target.addEventListener('drop', (event) => {
            event.preventDefault();
            if (selectedCard && (!ordered || target.classList.contains('order-target'))) placeCard(selectedCard, target);
        });
        if (!ordered) {
            target.addEventListener('click', (event) => {
                if (selectedCard && !event.target.closest('[data-unlock-card]')) placeCard(selectedCard, target);
            });
            target.addEventListener('keydown', (event) => {
                if (selectedCard && ['Enter', ' '].includes(event.key)) {
                    event.preventDefault();
                    placeCard(selectedCard, target);
                }
            });
        }
    });
    form.querySelectorAll('[data-move]').forEach((button) => {
        button.addEventListener('click', () => {
            const card = button.closest('[data-unlock-card]');
            const sibling = button.dataset.move === 'up' ? card.previousElementSibling : card.nextElementSibling;
            if (!sibling || !sibling.matches('[data-unlock-card]')) return;
            if (button.dataset.move === 'up') card.parentElement.insertBefore(card, sibling);
            else card.parentElement.insertBefore(sibling, card);
            syncAssignments();
            card.focus();
        });
    });
    form.addEventListener('submit', () => {
        syncAssignments();
        const button = form.querySelector('[data-check-unlock]');
        if (button) {
            button.disabled = true;
            button.innerHTML = '<span class="spinner-border spinner-border-sm" aria-hidden="true"></span> Checking...';
        }
    });
    syncAssignments();
});

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
    const formatStudentId = (value) => {
        const digits = value.replace(/\D/g, '').slice(0, 8);
        if (digits.length <= 2) return digits;
        if (digits.length <= 3) return `${digits.slice(0, 2)}-${digits.slice(2)}`;
        return `${digits.slice(0, 2)}-${digits.slice(2, 3)}-${digits.slice(3)}`;
    };
    input.addEventListener('input', () => {
        input.value = formatStudentId(input.value);
    });
    input.value = formatStudentId(input.value);
});

document.querySelectorAll('[data-auto-dismiss]').forEach((alert) => {
    const delay = Number.parseInt(alert.dataset.autoDismiss, 10);
    if (Number.isFinite(delay) && delay > 0) {
        window.setTimeout(() => alert.remove(), delay);
    }
});

const simulatorForm = document.querySelector('[data-security-simulator]');
const simulatorCode = document.querySelector('[data-simulator-code]');
if (simulatorForm && simulatorCode) {
    ['copy', 'cut', 'paste', 'drop', 'dragstart'].forEach((eventName) => simulatorCode.addEventListener(eventName, (event) => event.preventDefault()));
    document.querySelector('[data-simulator-reset]')?.addEventListener('click', () => {
        simulatorCode.value = '';
        simulatorCode.focus();
    });
    simulatorForm.addEventListener('submit', () => {
        const button = simulatorForm.querySelector('[data-simulator-run]');
        if (button) {
            button.disabled = true;
            button.innerHTML = '<span class="spinner-border spinner-border-sm" aria-hidden="true"></span> Running Security Check...';
        }
    });
}

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
        if (event.key === 'F12' ||
            ((event.ctrlKey || event.metaKey) && ['c', 'v', 'x', 'u'].includes(key)) ||
            (event.ctrlKey && event.shiftKey && ['i', 'j'].includes(key))) {
            event.preventDefault();
        }
    });
}
