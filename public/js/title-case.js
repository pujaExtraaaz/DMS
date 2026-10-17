/**
 * DMS Global Title Case Formatting for Name and Human-Readable Fields
 * 
 * Automatically formats names and human-readable text into consistent Title Case
 * (e.g., 'sony professional display' -> 'Sony Professional Display',
 *        'SONY PROFESSIONAL DISPLAY' -> 'Sony Professional Display',
 *        'apple iphone 18 pro' -> 'Apple Iphone 18 Pro',
 *        'bulk foods depot' -> 'Bulk Foods Depot',
 *        'finished goods' -> 'Finished Goods',
 *        'north zone' -> 'North Zone')
 * 
 * Strict exceptions:
 * - Does NOT format: email, password, credentials, gstin, pan, hsn, sku, part_number,
 *   technical codes, slugs, urls, api keys, tokens, serial numbers, batch numbers, phone numbers.
 * - Preserves standard acronyms (GSTIN, GST, PAN, SKU, HSN, HDMI, USB, LED, OLED, CPU, RAM, SSD, HDD, PLC, HMI, VFD, MCB, MCCB, UPS, AC, DC, PVC, etc.).
 * - Preserves model numbers / alphanumeric codes (e.g. FW-43EZ20L, INV-001).
 * - Formats on blur, paste, and form submit, preserving natural keystroke typing.
 */
(function () {
    'use strict';

    const ACRONYM_MAP = {
        'gstin': 'GSTIN',
        'gst': 'GST',
        'pan': 'PAN',
        'sku': 'SKU',
        'hsn': 'HSN',
        'od': 'OD',
        'api': 'API',
        'fy': 'FY',
        'uom': 'UOM',
        'uoms': 'UOMs',
        'id': 'ID',
        'ids': 'IDs',
        'crm': 'CRM',
        'hrms': 'HRMS',
        'hr': 'HR',
        'po': 'PO',
        'pi': 'PI',
        'ar': 'AR',
        'ap': 'AP',
        'neft': 'NEFT',
        'rtgs': 'RTGS',
        'upi': 'UPI',
        'itc': 'ITC',
        'gstr': 'GSTR',
        'gstr-1': 'GSTR-1',
        'gstr-2b': 'GSTR-2B',
        'gstr-3b': 'GSTR-3B',
        'tds': 'TDS',
        'tcs': 'TCS',
        'kpi': 'KPI',
        'kpis': 'KPIs',
        'yoy': 'YoY',
        'cvp': 'CVP',
        'bs': 'BS',
        'p&l': 'P&L',
        'csv': 'CSV',
        'pdf': 'PDF',
        'json': 'JSON',
        'url': 'URL',
        'uuid': 'UUID',
        'qr': 'QR',
        'html': 'HTML',
        'css': 'CSS',
        'js': 'JS',
        'erp': 'ERP',
        'dms': 'DMS',
        'lr': 'LR',
        'lr/rr': 'LR/RR',
        'tally': 'Tally',
        'sms': 'SMS',
        'otp': 'OTP',
        'rfq': 'RFQ',
        'rfqs': 'RFQs',
        'e-way': 'E-Way',
        'e-invoice': 'E-Invoice',
        'oled': 'OLED',
        'qled': 'QLED',
        'q1': 'Q1',
        'q2': 'Q2',
        'q3': 'Q3',
        'q4': 'Q4',
        'mrp': 'MRP',
        'po/pi': 'PO/PI',
        'hdmi': 'HDMI',
        'usb': 'USB',
        'led': 'LED',
        'cpu': 'CPU',
        'ram': 'RAM',
        'rom': 'ROM',
        'ssd': 'SSD',
        'hdd': 'HDD',
        'ip': 'IP',
        'lan': 'LAN',
        'wan': 'WAN',
        'wifi': 'Wi-Fi',
        'wi-fi': 'Wi-Fi',
        'vga': 'VGA',
        'dvi': 'DVI',
        'plc': 'PLC',
        'hmi': 'HMI',
        'vfd': 'VFD',
        'mcb': 'MCB',
        'mccb': 'MCCB',
        'acb': 'ACB',
        'rccb': 'RCCB',
        'ups': 'UPS',
        'ac': 'AC',
        'dc': 'DC',
        'pvc': 'PVC',
        'tv': 'TV',
        'tvs': 'TVs',
        'nvme': 'NVMe',
        'ddr': 'DDR',
        'ddr2': 'DDR2',
        'ddr3': 'DDR3',
        'ddr4': 'DDR4',
        'ddr5': 'DDR5',
        'xlpe': 'XLPE',
        'cctv': 'CCTV',
        'dvr': 'DVR',
        'nvr': 'NVR',
        'ai': 'AI',
        'ml': 'ML',
        'iot': 'IoT',
        'pos': 'POS',
        'llp': 'LLP',
        'pvt': 'Pvt',
        'ltd': 'Ltd',
        'inc': 'Inc',
        'corp': 'Corp',
        'co': 'Co'
    };

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
        'part_number',
        'hsn_code',
        'code',
        'serial_no',
        'batch_no',
        'token',
        'url',
        'slug',
        'search',
        'q',
        '_token',
        '_method',
        'api_key',
        'secret'
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
        'batch_name',
        'product_type',
        'product_type_name',
        'classification',
        'customer_type',
        'department',
        'designation',
        'leave_type',
        'expense_type',
        'city',
        'state',
        'country'
    ]);

    function toTitleCase(text) {
        if (!text || typeof text !== 'string') return '';

        // Preserve raw URLs or email addresses untouched
        if (text.includes('://') || (text.includes('@') && !text.includes(' '))) {
            return text;
        }

        // Normalize multiple spaces / tabs to single space
        const trimmed = text.replace(/[ \t]+/g, ' ').trim();
        if (!trimmed) return '';

        const tokenRegex = /[A-Za-z0-9]+(?:['\/\-\&][A-Za-z0-9]+)*/g;

        return trimmed.replace(tokenRegex, function (token) {
            const lower = token.toLowerCase();

            // 1. Check known acronyms or abbreviations
            if (Object.prototype.hasOwnProperty.call(ACRONYM_MAP, lower)) {
                return ACRONYM_MAP[lower];
            }

            // 2. Technical units with digits: e.g. 4k -> 4K, 16gb -> 16GB, 1tb -> 1TB
            const unitMatch = token.match(/^([0-9]+)(k|gb|tb|mb|kb|ghz|mhz|khz)$/i);
            if (unitMatch) {
                return unitMatch[1] + unitMatch[2].toUpperCase();
            }

            // 3. Preserve already capitalized alphanumeric codes (e.g. FW-43EZ20L, INV-001, 27AAPFU0939F1ZV)
            if (/\d/.test(token) && /[A-Z]/.test(token) && token === token.toUpperCase()) {
                return token;
            }

            // 3. Handle apostrophes (e.g. supplier's, o'connor)
            if (token.includes("'")) {
                const parts = token.split("'");
                if (parts.length === 2 && parts[1].toLowerCase() === 's') {
                    const baseLower = parts[0].toLowerCase();
                    const base = ACRONYM_MAP[baseLower] || (baseLower.charAt(0).toUpperCase() + baseLower.slice(1));
                    return base + "'s";
                }
                return parts.map(function (p) {
                    const pLower = p.toLowerCase();
                    return ACRONYM_MAP[pLower] || (pLower.charAt(0).toUpperCase() + pLower.slice(1));
                }).join("'");
            }

            // 4. Handle compound tokens separated by slash, hyphen, or ampersand
            if (/[\/\-\&]/.test(token)) {
                const subParts = token.split(/([\/\-\&])/);
                return subParts.map(function (part) {
                    if (part === '/' || part === '-' || part === '&') {
                        return part;
                    }
                    const partLower = part.toLowerCase();
                    if (Object.prototype.hasOwnProperty.call(ACRONYM_MAP, partLower)) {
                        return ACRONYM_MAP[partLower];
                    }
                    if (/\d/.test(part) && /[A-Z]/.test(part) && part === part.toUpperCase()) {
                        return part;
                    }
                    return partLower.charAt(0).toUpperCase() + partLower.slice(1);
                }).join('');
            }

            // 5. Default Title Case
            return lower.charAt(0).toUpperCase() + lower.slice(1);
        });
    }

    // Expose globally for manual formatting if needed
    window.dmsToTitleCase = toTitleCase;

    function isNameElement(el) {
        if (!el || (el.tagName !== 'INPUT' && el.tagName !== 'TEXTAREA')) {
            return false;
        }

        if (EXCLUDED_TYPES.has(el.type)) {
            return false;
        }

        if (el.dataset.noTitleCase === 'true' || el.dataset.lowercase === 'true' || el.dataset.noUppercase === 'true') {
            return false;
        }

        if (el.dataset.titleCase === 'true' || el.dataset.isName === 'true') {
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

    function formatInputElement(el) {
        if (!el || typeof el.value !== 'string') return;
        const raw = el.value;
        if (!raw.trim()) return;

        const formatted = toTitleCase(raw);
        if (formatted !== raw) {
            el.value = formatted;
            // Sync with reactive frameworks (Alpine, Livewire, etc.)
            el.dispatchEvent(new Event('input', { bubbles: true }));
            el.dispatchEvent(new Event('change', { bubbles: true }));
        }
    }

    // 1. Delegated blur listener (formats field when user finishes typing and leaves)
    document.addEventListener('blur', function (e) {
        const target = e.target;
        if (isNameElement(target)) {
            formatInputElement(target);
        }
    }, true);

    // 2. Delegated paste event listener
    document.addEventListener('paste', function (e) {
        const target = e.target;
        if (isNameElement(target)) {
            setTimeout(function () {
                formatInputElement(target);
            }, 10);
        }
    }, true);

    // 3. Delegated form submit listener (ensures all fields are formatted before payload is sent)
    document.addEventListener('submit', function (e) {
        const form = e.target;
        if (form && form.querySelectorAll) {
            const inputs = form.querySelectorAll('input:not([type="password"]):not([type="hidden"]):not([type="file"]), textarea');
            inputs.forEach(function (input) {
                if (isNameElement(input)) {
                    formatInputElement(input);
                }
            });
        }
    }, true);

    // 4. Mark existing inputs and format pre-filled names if necessary
    function markAndFormatElement(el) {
        if (isNameElement(el)) {
            el.setAttribute('data-is-name', 'true');
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

    // 5. MutationObserver for dynamic modals, Alpine components, and added DOM rows
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
