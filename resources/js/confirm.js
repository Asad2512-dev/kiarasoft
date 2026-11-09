// Global, accessible confirmation dialog (replaces window.confirm).
// window.ksConfirm({ title, message, confirmText, tone }) -> Promise<boolean>
// Forms are confirmed automatically when they: sign out, use DELETE / PUT / PATCH,
// or carry data-confirm="message". Opt out with data-no-confirm.

let modalEl, modal, resolver;

function build() {
    modalEl = document.createElement('div');
    modalEl.className = 'modal fade ks-confirm';
    modalEl.tabIndex = -1;
    modalEl.setAttribute('aria-hidden', 'true');
    modalEl.setAttribute('aria-labelledby', 'ksConfirmTitle');
    modalEl.innerHTML = `
      <div class="modal-dialog modal-dialog-centered" style="max-width: 420px">
        <div class="modal-content border-0 shadow-lg">
          <div class="modal-body p-4 text-center">
            <div class="ks-confirm-icon mx-auto mb-3"><i class="bi"></i></div>
            <h2 class="h5 mb-2" id="ksConfirmTitle"></h2>
            <p class="text-muted mb-0 ks-confirm-msg"></p>
          </div>
          <div class="modal-footer border-0 pt-0 px-4 pb-4 d-flex gap-2">
            <button type="button" class="btn btn-white flex-fill" data-ks-cancel>Cancel</button>
            <button type="button" class="btn flex-fill" data-ks-ok></button>
          </div>
        </div>
      </div>`;
    document.body.appendChild(modalEl);
    modal = new window.bootstrap.Modal(modalEl);
    const done = (v) => { if (resolver) { resolver(v); resolver = null; } modal.hide(); };
    modalEl.querySelector('[data-ks-ok]').addEventListener('click', () => done(true));
    modalEl.querySelector('[data-ks-cancel]').addEventListener('click', () => done(false));
    modalEl.addEventListener('hidden.bs.modal', () => { if (resolver) { resolver(false); resolver = null; } });
    modalEl.addEventListener('shown.bs.modal', () => modalEl.querySelector('[data-ks-ok]').focus());
}

const tones = {
    danger: { icon: 'exclamation-triangle', btn: 'btn-danger' },
    warning: { icon: 'question-circle', btn: 'btn-primary' },
    signout: { icon: 'box-arrow-right', btn: 'btn-primary' },
};

export function ksConfirm({ title = 'Are you sure?', message = '', confirmText = 'Confirm', tone = 'warning' } = {}) {
    if (!modalEl) build();
    // Close any other open Bootstrap modal first (e.g. category edit form).
    const t = tones[tone] || tones.warning;
    modalEl.dataset.tone = tone;
    modalEl.querySelector('.ks-confirm-icon i').className = 'bi bi-' + t.icon;
    modalEl.querySelector('#ksConfirmTitle').textContent = title;
    modalEl.querySelector('.ks-confirm-msg').textContent = message;
    const ok = modalEl.querySelector('[data-ks-ok]');
    ok.className = 'btn flex-fill ' + t.btn;
    ok.textContent = confirmText;
    return new Promise((resolve) => { resolver = resolve; modal.show(); });
}

function describe(form) {
    if (form.hasAttribute('data-no-confirm')) return null;
    const method = (form.querySelector('input[name="_method"]')?.value || form.method || 'get').toUpperCase();
    const custom = form.dataset.confirm;
    const action = form.getAttribute('action') || '';
    if (/\/logout$/.test(action)) {
        return { title: 'Sign out?', message: custom || 'You will need to sign in again to continue.', confirmText: 'Sign out', tone: 'signout' };
    }
    if (method === 'DELETE') {
        return { title: form.dataset.confirmTitle || 'Archive this record?', message: custom || 'This item will be archived. Historical records are kept.', confirmText: form.dataset.confirmButton || 'Yes, archive', tone: 'danger' };
    }
    if (method === 'PUT' || method === 'PATCH') {
        return { title: form.dataset.confirmTitle || 'Save changes?', message: custom || 'Please confirm you want to apply these changes.', confirmText: form.dataset.confirmButton || 'Yes, save', tone: form.dataset.confirmTone || 'warning' };
    }
    if (custom) {
        return { title: form.dataset.confirmTitle || 'Please confirm', message: custom, confirmText: form.dataset.confirmButton || 'Confirm', tone: form.dataset.confirmTone || 'warning' };
    }
    return null;
}

export function initConfirmations() {
    // Capture phase: runs before the double-submit lock and any other submit handlers.
    document.addEventListener('submit', async (e) => {
        const form = e.target;
        if (!(form instanceof HTMLFormElement)) return;
        if (form.dataset.ksConfirmed === '1') { delete form.dataset.ksConfirmed; return; }
        const opts = describe(form);
        if (!opts) return;
        e.preventDefault();
        e.stopImmediatePropagation();
        const submitter = e.submitter;
        // If the form sits inside an open modal, hide that modal while asking.
        const parentModal = form.closest('.modal.show');
        if (parentModal) window.bootstrap.Modal.getInstance(parentModal)?.hide();
        if (await ksConfirm(opts)) {
            form.dataset.ksConfirmed = '1';
            submitter && form.contains(submitter) ? form.requestSubmit(submitter) : form.requestSubmit();
        } else if (parentModal) {
            window.bootstrap.Modal.getOrCreateInstance(parentModal).show();
        }
    }, true);
}
