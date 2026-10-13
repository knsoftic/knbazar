/* KN Bazar - progressive enhancements (vanilla JS, no build step).
   Every feature degrades gracefully: forms and links work without JS. */
(function () {
    'use strict';

    const $ = (sel, root = document) => root.querySelector(sel);
    const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));
    const csrf = () => ($('meta[name="csrf-token"]') || {}).content || '';

    /* ---------- Toasts ---------- */
    function toast(message, type = 'success') {
        let container = $('#toastContainer');
        if (!container || !window.bootstrap) { alert(message); return; }
        const el = document.createElement('div');
        el.className = 'toast align-items-center border-0 text-bg-' + (type === 'error' ? 'danger' : type);
        el.setAttribute('role', 'status');
        el.setAttribute('aria-live', 'polite');
        el.innerHTML = '<div class="d-flex"><div class="toast-body"></div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button></div>';
        $('.toast-body', el).textContent = message;
        container.appendChild(el);
        const t = new bootstrap.Toast(el, { delay: 3500 });
        el.addEventListener('hidden.bs.toast', () => el.remove());
        t.show();
    }
    window.knToast = toast;

    /* ---------- Confirmation modal for destructive actions ---------- */
    function initConfirm() {
        const modalEl = $('#confirmModal');
        if (!modalEl || !window.bootstrap) return;
        const modal = new bootstrap.Modal(modalEl);
        let pendingForm = null;

        document.addEventListener('submit', (e) => {
            const form = e.target;
            if (!(form instanceof HTMLFormElement) || !form.dataset.confirm || form.dataset.confirmed === '1') return;
            e.preventDefault();
            pendingForm = form;
            $('.confirm-message', modalEl).textContent = form.dataset.confirm;
            const btn = $('.confirm-ok', modalEl);
            btn.className = 'btn confirm-ok ' + (form.dataset.confirmClass || 'btn-danger');
            btn.textContent = form.dataset.confirmButton || 'Yes, continue';
            modal.show();
        }, true);

        $('.confirm-ok', modalEl).addEventListener('click', () => {
            if (!pendingForm) return;
            pendingForm.dataset.confirmed = '1';
            modal.hide();
            if (pendingForm.requestSubmit) { pendingForm.requestSubmit(); } else { pendingForm.submit(); }
        });
    }

    /* ---------- Reason modal (reject product/payment/publisher) ---------- */
    function initReasonModal() {
        const modalEl = $('#reasonModal');
        if (!modalEl) return;
        modalEl.addEventListener('show.bs.modal', (e) => {
            const trigger = e.relatedTarget;
            if (!trigger) return;
            const form = $('form', modalEl);
            form.action = trigger.dataset.action;
            $('.modal-title', modalEl).textContent = trigger.dataset.title || 'Provide a reason';
            $('.reason-help', modalEl).textContent = trigger.dataset.help || 'The user will see this reason.';
            $('textarea', modalEl).value = '';
        });
        modalEl.addEventListener('shown.bs.modal', () => $('textarea', modalEl).focus());
    }

    /* ---------- Stacked mobile tables: label each cell from its header ---------- */
    function initStackTables() {
        $$('table.table-stack').forEach((table) => {
            const headers = $$('thead th', table).map((th) => th.textContent.trim());
            $$('tbody tr', table).forEach((tr) => {
                $$('td', tr).forEach((td, i) => {
                    if (!td.hasAttribute('data-label')) td.setAttribute('data-label', headers[i] || '');
                });
            });
        });
    }

    /* ---------- Image preview for file inputs ---------- */
    function initPreviews() {
        $$('input[type="file"][data-preview]').forEach((input) => {
            input.addEventListener('change', () => {
                const target = $(input.dataset.preview);
                const file = input.files && input.files[0];
                if (!target || !file || !file.type.startsWith('image/')) return;
                const url = URL.createObjectURL(file);
                target.src = url;
                target.classList.remove('d-none');
                target.onload = () => URL.revokeObjectURL(url);
            });
        });
        $$('input[type="file"][data-max-mb]').forEach((input) => {
            input.addEventListener('change', () => {
                const max = parseFloat(input.dataset.maxMb) * 1024 * 1024;
                const tooBig = Array.from(input.files || []).some((f) => f.size > max);
                input.setCustomValidity(tooBig ? 'File is larger than ' + input.dataset.maxMb + ' MB.' : '');
                if (tooBig) input.reportValidity();
            });
        });
    }

    /* ---------- Slug generation ---------- */
    const slugify = (text) => text.toString().normalize('NFKD').replace(/[̀-ͯ]/g, '').toLowerCase().trim()
        .replace(/[^a-z0-9\s-]/g, '').replace(/[\s_-]+/g, '-').replace(/^-+|-+$/g, '');

    function initSlugs() {
        $$('[data-slug-source]').forEach((source) => {
            const target = $(source.dataset.slugSource);
            if (!target) return;
            let touched = target.value.length > 0;
            target.addEventListener('input', () => { touched = target.value.length > 0; });
            source.addEventListener('input', () => { if (!touched) target.value = slugify(source.value); });
            target.addEventListener('blur', () => { target.value = slugify(target.value); });
        });
    }

    /* ---------- Free / paid toggle in product forms ---------- */
    function initPriceToggle() {
        const toggle = $('[data-free-toggle]');
        if (!toggle) return;
        const fields = $$('[data-paid-only]');
        const sync = () => fields.forEach((el) => {
            el.classList.toggle('d-none', toggle.checked);
            $$('input', el).forEach((input) => { input.disabled = toggle.checked; });
        });
        toggle.addEventListener('change', sync);
        sync();
    }

    /* ---------- Character counters (SEO fields) ---------- */
    function initCounters() {
        $$('[data-counter]').forEach((input) => {
            const ideal = parseInt(input.dataset.counter, 10);
            const counter = document.createElement('div');
            counter.className = 'char-counter text-end';
            input.insertAdjacentElement('afterend', counter);
            const update = () => {
                const n = input.value.length;
                counter.textContent = n + ' / ' + ideal + ' recommended';
                counter.classList.toggle('over', n > ideal);
            };
            input.addEventListener('input', update);
            update();
        });
    }

    /* ---------- Repeater (payment gateway account fields) ---------- */
    function initRepeaters() {
        $$('[data-repeater]').forEach((wrap) => {
            const list = $('[data-repeater-list]', wrap);
            const template = $('template', wrap);
            let index = $$('[data-repeater-item]', list).length;
            $('[data-repeater-add]', wrap).addEventListener('click', () => {
                const html = template.innerHTML.replace(/__INDEX__/g, String(index++));
                list.insertAdjacentHTML('beforeend', html);
            });
            list.addEventListener('click', (e) => {
                const btn = e.target.closest('[data-repeater-remove]');
                if (btn) btn.closest('[data-repeater-item]').remove();
            });
        });
    }

    /* ---------- Checkout: gateway instructions ---------- */
    function initGatewayChooser() {
        const radios = $$('input[name="payment_gateway_id"]');
        if (!radios.length) return;
        const sync = () => {
            radios.forEach((r) => {
                const panel = $('#gateway-info-' + r.value);
                if (panel) panel.classList.toggle('d-none', !r.checked);
                const label = r.closest('.gateway-option');
                if (label) label.classList.toggle('active', r.checked);
            });
        };
        radios.forEach((r) => r.addEventListener('change', sync));
        sync();
    }

    /* ---------- Product gallery ---------- */
    function initGallery() {
        const main = $('#galleryMain');
        if (!main) return;
        $$('.gallery-thumbs button').forEach((btn) => {
            btn.addEventListener('click', () => {
                main.src = btn.dataset.full;
                main.alt = btn.dataset.alt || main.alt;
                $$('.gallery-thumbs button').forEach((b) => b.classList.toggle('active', b === btn));
            });
        });
    }

    /* ---------- AJAX add to cart ---------- */
    function initAjaxCart() {
        document.addEventListener('submit', async (e) => {
            const form = e.target;
            if (!(form instanceof HTMLFormElement) || !form.matches('[data-ajax-cart]') || !window.fetch) return;
            e.preventDefault();
            const btn = $('button[type="submit"]', form);
            if (btn) btn.disabled = true;
            try {
                const res = await fetch(form.action, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf(), 'X-Requested-With': 'XMLHttpRequest' },
                    body: new FormData(form),
                    credentials: 'same-origin',
                });
                const data = await res.json().catch(() => ({}));
                if (res.ok) {
                    $$('[data-cart-count]').forEach((el) => { el.textContent = data.count; el.classList.toggle('d-none', !data.count); });
                    toast(data.message || 'Added to cart.');
                    if (btn) { btn.innerHTML = '<i class="bi bi-check2"></i> In cart'; }
                } else if (res.status === 419) {
                    window.location.reload();
                } else {
                    toast(data.message || 'Could not add this product to your cart.', 'error');
                    if (btn) btn.disabled = false;
                }
            } catch (err) {
                form.removeAttribute('data-ajax-cart');
                form.submit();
            }
        });
    }

    /* ---------- Copy to clipboard ---------- */
    function initCopy() {
        document.addEventListener('click', async (e) => {
            const btn = e.target.closest('[data-copy]');
            if (!btn) return;
            try {
                await navigator.clipboard.writeText(btn.dataset.copy);
                toast('Copied to clipboard.');
            } catch (err) {
                toast('Copy failed - please copy it manually.', 'error');
            }
        });
    }

    /* ---------- Bulk selection (admin tables) ---------- */
    function initBulk() {
        const all = $('[data-check-all]');
        if (!all) return;
        const boxes = () => $$('[data-check-item]');
        const bar = $('[data-bulk-bar]');
        const sync = () => {
            const n = boxes().filter((b) => b.checked).length;
            if (bar) { bar.classList.toggle('d-none', n === 0); $('[data-bulk-count]', bar).textContent = n; }
        };
        all.addEventListener('change', () => { boxes().forEach((b) => { b.checked = all.checked; }); sync(); });
        document.addEventListener('change', (e) => { if (e.target.matches('[data-check-item]')) sync(); });
        sync();
    }

    /* ---------- Sidebar collapse (desktop) ---------- */
    function initSidebar() {
        const btn = $('[data-sidebar-collapse]');
        if (!btn) return;
        try { if (localStorage.getItem('kn-sidebar') === '1') document.body.classList.add('sidebar-collapsed'); } catch (e) {}
        btn.addEventListener('click', () => {
            document.body.classList.toggle('sidebar-collapsed');
            try { localStorage.setItem('kn-sidebar', document.body.classList.contains('sidebar-collapsed') ? '1' : '0'); } catch (e) {}
        });
    }

    /* ---------- Charts (Chart.js, admin & publisher dashboards) ---------- */
    function initCharts() {
        if (!window.Chart) return;
        Chart.defaults.font.family = getComputedStyle(document.body).fontFamily;
        Chart.defaults.color = '#64748b';
        const palette = ['#4f46e5', '#0ea5e9', '#16a34a', '#f59e0b', '#db2777', '#7c3aed'];
        $$('canvas[data-chart]').forEach((canvas, i) => {
            let cfg;
            try { cfg = JSON.parse(canvas.dataset.chart); } catch (e) { return; }
            const color = cfg.color || palette[i % palette.length];
            const isBar = cfg.type === 'bar';
            new Chart(canvas, {
                type: cfg.type || 'line',
                data: {
                    labels: cfg.labels,
                    datasets: [{
                        label: cfg.label,
                        data: cfg.data,
                        borderColor: color,
                        backgroundColor: isBar ? color + 'cc' : color + '1f',
                        fill: !isBar,
                        tension: .35,
                        borderWidth: 2,
                        pointRadius: 0,
                        pointHoverRadius: 4,
                        borderRadius: isBar ? 6 : 0,
                        maxBarThickness: 36,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: { legend: { display: false } },
                    indexAxis: cfg.horizontal ? 'y' : 'x',
                    scales: {
                        x: { grid: { display: false }, ticks: { maxTicksLimit: 8, autoSkip: true } },
                        y: { beginAtZero: true, grid: { color: '#eef2f7' }, ticks: { precision: 0, maxTicksLimit: 6 } },
                    },
                },
            });
        });
    }

    /* ---------- Auto-submit selects (filters) ---------- */
    function initAutoSubmit() {
        $$('[data-autosubmit]').forEach((el) => el.addEventListener('change', () => el.form && el.form.submit()));
    }

    /* ---------- Custom date range toggle (reports) ---------- */
    function initRangeToggle() {
        const select = $('[data-range-select]');
        if (!select) return;
        const custom = $$('[data-range-custom]');
        const sync = () => custom.forEach((el) => el.classList.toggle('d-none', select.value !== 'custom'));
        select.addEventListener('change', sync);
        sync();
    }

    /* ---------- Prevent double submits ---------- */
    function initSubmitLock() {
        document.addEventListener('submit', (e) => {
            const form = e.target;
            if (!(form instanceof HTMLFormElement) || form.dataset.ajaxCart !== undefined) return;
            if (form.dataset.confirm && form.dataset.confirmed !== '1') return;
            setTimeout(() => {
                if (e.defaultPrevented) return;
                $$('button[type="submit"][data-loading]', form).forEach((btn) => {
                    btn.disabled = true;
                    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>' + btn.dataset.loading;
                });
            }, 0);
        });
    }

    /* ---------- Bootstrap tooltips ---------- */
    function initTooltips() {
        if (!window.bootstrap) return;
        $$('[data-bs-toggle="tooltip"]').forEach((el) => new bootstrap.Tooltip(el));
    }

    document.addEventListener('DOMContentLoaded', () => {
        initTooltips();
        initConfirm();
        initReasonModal();
        initStackTables();
        initPreviews();
        initSlugs();
        initPriceToggle();
        initCounters();
        initRepeaters();
        initGatewayChooser();
        initGallery();
        initAjaxCart();
        initCopy();
        initBulk();
        initSidebar();
        initCharts();
        initAutoSubmit();
        initRangeToggle();
        initSubmitLock();
    });
})();
