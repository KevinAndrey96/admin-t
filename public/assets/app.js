// Progressive enhancements: every form keeps working without JavaScript.

for (const button of document.querySelectorAll('[data-dismiss]')) {
    button.addEventListener('click', () => button.closest('.flash')?.remove());
}

for (const form of document.querySelectorAll('form[data-confirm]')) {
    form.addEventListener('submit', (event) => {
        if (!window.confirm(form.dataset.confirm)) {
            event.preventDefault();
        }
    });
}

// Avoid duplicated status changes, notes or products when someone double-clicks.
for (const form of document.querySelectorAll('form[data-busy]')) {
    form.addEventListener('submit', () => {
        for (const button of form.querySelectorAll('button[type="submit"]')) {
            setTimeout(() => { button.disabled = true; }, 0);
        }
    });
}

for (const toggle of document.querySelectorAll('[data-toggles]')) {
    const target = document.getElementById(toggle.dataset.toggles);
    if (!target) {
        continue;
    }
    const sync = () => { target.disabled = !toggle.checked; };
    toggle.addEventListener('change', sync);
    sync();
}

const previewInput = document.querySelector('[data-preview-input]');
const preview = document.querySelector('[data-preview]');
if (previewInput && preview) {
    let objectUrl = null;
    previewInput.addEventListener('change', () => {
        const [file] = previewInput.files;
        if (!file) {
            return;
        }
        if (objectUrl) {
            URL.revokeObjectURL(objectUrl);
        }
        objectUrl = URL.createObjectURL(file);
        const image = document.createElement('img');
        image.src = objectUrl;
        image.alt = 'Vista previa de la nueva imagen';
        preview.replaceChildren(image);
    });
}
