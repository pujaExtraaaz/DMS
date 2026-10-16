/**
 * DMS Global Full Uppercase Formatting for Name Fields
 * 
 * Automatically transforms typed and pasted values in all name fields to uppercase,
 * while strictly preserving cursor selection position without jumps.
 */
(function () {
    'use strict';

    const EXCLUDED_FIELDS = new Set([
        'username',
        'user_name',
        'email',
        'secondary_email',
        'contact_email',
        'password',
        'password_confirmation',
        'file_name',
        'filename',
        'route_name',
        'permission_name',
        'guard_name',
        'class_name',
        'component_name',
        'table_name',
        'database_name',
        'disk_name',
        'column_name',
        'hostname',
        'domain_name',
        'gstin',
        'pan',
        'sku',
        'hsn_code',
        'code',
        'serial_no',
        'token',
        'url',
        'slug',
        'search',
        'q',
        '_token',
        '_method'
    ]);

    const EXCLUDED_TYPES = new Set([
        'password',
        'email',
        'url',
        'number',
        'date',
        'datetime-local',
        'time',
        'checkbox',
        'radio',
        'file',
        'hidden',
        'range',
        'color'
    ]);

    const EXPLICIT_NAME_FIELDS = new Set([
        'name',
        'legal_name',
        'trade_name',
        'company_name',
        'transporter_name',
        'driver_name',
        'customer_name',
        'supplier_name',
        'party_name',
        'contact_person',
        'contact_name',
        'first_name',
        'last_name',
        'middle_name',
        'site_name',
        'bank_name',
        'branch_name',
        'account_holder_name',
        'shipping_name',
        'actor_name',
        'approver_name',
        'created_by_name',
        'updated_by_name',
        'approved_by_name',
        'converted_by_name',
        'cancelled_by_name',
        'salesperson_name',
        'kpi_name',
        'form_name',
        'tally_name',
        'batch_name'
    ]);

    function isNameElement(el) {
        if (!el || (el.tagName !== 'INPUT' && el.tagName !== 'TEXTAREA')) {
            return false;
        }

        if (EXCLUDED_TYPES.has(el.type)) {
            return false;
        }

        if (el.dataset.noUppercase === 'true' || el.dataset.lowercase === 'true') {
            return false;
        }

        if (el.dataset.isName === 'true' || el.dataset.uppercase === 'true') {
            return true;
        }

        const nameAttr = (el.getAttribute('name') || '').toLowerCase().trim();
        const idAttr = (el.getAttribute('id') || '').toLowerCase().trim();

        // Extract clean field name from bracketed array: items[0][name] -> name
        let cleanName = nameAttr;
        const bracketMatch = nameAttr.match(/\[([^\]]+)\]$/);
        if (bracketMatch) {
            cleanName = bracketMatch[1];
        }

        if (EXCLUDED_FIELDS.has(cleanName) || EXCLUDED_FIELDS.has(nameAttr) || EXCLUDED_FIELDS.has(idAttr)) {
            return false;
        }

        if (EXPLICIT_NAME_FIELDS.has(cleanName) || EXPLICIT_NAME_FIELDS.has(idAttr)) {
            return true;
        }

        if (cleanName.endsWith('_name') || idAttr.endsWith('_name')) {
            return true;
        }

        // Check placeholder or aria-label for name mentions (e.g., "Enter Product Name", "Customer Name")
        const placeholder = (el.getAttribute('placeholder') || '').toLowerCase();
        if (placeholder.includes('name') && !placeholder.includes('user') && !placeholder.includes('file')) {
            return true;
        }

        return false;
    }

    function uppercaseInputPreservingCursor(el) {
        if (!el || typeof el.value !== 'string') return;

        const original = el.value;
        const upper = original.toUpperCase();

        if (original !== upper) {
            const start = el.selectionStart;
            const end = el.selectionEnd;

            el.value = upper;

            if (start !== null && end !== null) {
                el.setSelectionRange(start, end);
            }

            // Sync with reactive frameworks (Alpine, Livewire, etc.)
            el.dispatchEvent(new Event('input', { bubbles: true }));
        }
    }

    // 1. Delegated capture input event listener
    document.addEventListener('input', function (e) {
        const target = e.target;
        if (isNameElement(target)) {
            const original = target.value;
            if (typeof original === 'string') {
                const upper = original.toUpperCase();
                if (original !== upper) {
                    const start = target.selectionStart;
                    const end = target.selectionEnd;
                    target.value = upper;
                    if (start !== null && end !== null) {
                        target.setSelectionRange(start, end);
                    }
                }
            }
        }
    }, true);

    // 2. Delegated paste event listener
    document.addEventListener('paste', function (e) {
        const target = e.target;
        if (isNameElement(target)) {
            setTimeout(function () {
                uppercaseInputPreservingCursor(target);
            }, 0);
        }
    }, true);

    // 3. Mark and format all existing inputs
    function markAndFormatElement(el) {
        if (isNameElement(el)) {
            el.classList.add('uppercase');
            el.setAttribute('data-is-name', 'true');
            if (el.value) {
                const upper = el.value.toUpperCase();
                if (el.value !== upper) {
                    el.value = upper;
                }
            }
        }
    }

    function initExistingInputs() {
        const selector = 'input:not([type="password"]):not([type="hidden"]):not([type="file"]), textarea';
        const inputs = document.querySelectorAll(selector);
        inputs.forEach(markAndFormatElement);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initExistingInputs);
    } else {
        initExistingInputs();
    }

    // 4. MutationObserver for dynamic modals, Alpine components, and added DOM rows
    const observer = new MutationObserver(function (mutations) {
        for (let i = 0; i < mutations.length; i++) {
            const added = mutations[i].addedNodes;
            for (let j = 0; j < added.length; j++) {
                const node = added[j];
                if (node.nodeType === 1) { // ELEMENT_NODE
                    markAndFormatElement(node);
                    const subInputs = node.querySelectorAll ? node.querySelectorAll('input, textarea') : [];
                    for (let k = 0; k < subInputs.length; k++) {
                        markAndFormatElement(subInputs[k]);
                    }
                }
            }
        }
    });

    observer.observe(document.body || document.documentElement, {
        childList: true,
        subtree: true
    });
})();

