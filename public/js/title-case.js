/**
 * DMS Global Title Case Formatting for Human-Readable Text Fields
 * 
 * Automatically capitalizes the first letter of every word across all applicable
 * input fields throughout the entire DMS project.
 * 
 * Examples:
 * - sakshi chavan -> Sakshi Chavan
 * - punjab national bank -> Punjab National Bank
 * - private limited company -> Private Limited Company
 * - trojan distributors -> Trojan Distributors
 * - head office / billing -> Head Office / Billing
 * 
 * Strict exceptions:
 * - Does NOT format: email, password, authentication fields.
 * - Does NOT format: GSTIN, PAN, CIN, TAN, IFSC, HSN, SKU, part_number, serial_number, barcode, statutory IDs.
 * - Does NOT format: auto-generated codes, transaction numbers, account numbers, voucher/invoice numbers, phone numbers, pincodes.
 * - Does NOT format: URLs, websites, API keys, tokens, filenames, slugs.
 * - Does NOT format: enum slugs (party_type, account_type, status, etc.).
 * - Does NOT format: long freeform sentences/paragraphs (description, notes, remarks).
 * - Does NOT format: numeric, currency, date, time, calculation fields.
 * - Preserves standard acronyms (GSTIN, GST, PAN, SKU, HSN, HDMI, USB, LED, OLED, CPU, RAM, SSD, HDD, PLC, HMI, VFD, MCB, MCCB, UPS, AC, DC, PVC, etc.).
 * - Preserves model numbers / alphanumeric codes (e.g. FW-43EZ20L, INV-001).
 * - Real-time capitalization while typing preserves cursor position and normal keystroke typing.
 * - Formats on blur, paste, and form submit.
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
        'hq': 'HQ',
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
        'midc': 'MIDC',
        'mh': 'MH',
        'dl': 'DL',
        'ka': 'KA',
        'tn': 'TN',
        'gj': 'GJ',
        'up': 'UP',
        'mp': 'MP',
        'wb': 'WB',
        'rj': 'RJ',
        'pb': 'PB',
        'ga': 'GA',
        'kl': 'KL',
        'ap': 'AP',
        'ts': 'TS',
        'sap': 'SAP',
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
        'email',
        'secondary_email',
        'contact_email',
        'password',
        'password_confirmation',
        'current_password',
        'gstin',
        'supplier_gstin',
        'customer_gstin',
        'pan',
        'cin',
        'tan',
        'ifsc',
        'bank_ifsc',
        'ifsc_code',
        'beneficiary_ifsc',
        'hsn',
        'hsn_code',
        'sku',
        'part_number',
        'serial_no',
        'serial_number',
        'serial_numbers',
        'barcode',
        'barcode_scan',
        'bsr_code',
        'udyam_registration_no',
        'msme_registration_no',
        'iec_code',
        'incorporation_no',
        'contract_number',
        'certificate_number',
        'code',
        'asset_code',
        'section_code',
        'account_number',
        'account_no',
        'bank_account_no',
        'beneficiary_account',
        'invoice_no',
        'invoice_number',
        'supplier_invoice_number',
        'order_no',
        'po_no',
        'pi_no',
        'lr_no',
        'challan_no',
        'challan_number',
        'slip_number',
        'voucher_number',
        'voucher_no',
        'transaction_no',
        'reference',
        'reference_no',
        'reference_number',
        'loan_number',
        'advice_number',
        'registration_no',
        'vehicle_no',
        'cheque_number',
        'cheque_no',
        'batch_no',
        'batch_number',
        'utr',
        'upi_id',
        'pincode',
        'pin_code',
        'zip',
        'postal_code',
        'phone',
        'mobile',
        'contact_phone',
        'secondary_mobile',
        'fax',
        'alternate_phone',
        'url',
        'website',
        'catalog_link',
        'api_key',
        'secret',
        'token',
        '_token',
        '_method',
        'slug',
        'file',
        'logo_path',
        'path',
        'auth_header',
        'tally_file',
        'receipt_file',
        'logo',
        'logo_emoji',
        'search',
        'q',
        'shortcut-search',
        'sort',
        'filter',
        'action',
        'actions',
        'method',
        'trigger',
        'confirm',
        'confirmation',
        'confirm-user-deletion',
        'remember',
        'tab',
        'rate',
        'amount',
        'price',
        'cost',
        'tax',
        'discount',
        'total',
        'subtotal',
        'qty',
        'quantity',
        'balance',
        'limit',
        'od_limit',
        'interest_rate',
        'cgst',
        'sgst',
        'igst',
        'cess',
        'percentage',
        'percent',
        'margin',
        'mrp',
        'weight',
        'width_cm',
        'width_in',
        'tenure_months',
        'warranty_months',
        'capacity',
        'conversion_factor',
        'annual_turnover',
        'authorized_capital',
        'paid_up_capital',
        'useful_life_years',
        'single_limit',
        'unit_cost',
        'unit_price',
        'selling_price',
        'purchase_price',
        'trade_price',
        'benefit_amount',
        'benefit_percent',
        'min_qty',
        'max_amount',
        'min_amount',
        'min_stock',
        'max_stock',
        'reorder_level',
        'view_name',
        'id',
        // Enum slugs and technical options
        'type',
        'party_type',
        'party_role',
        'account_type',
        'discount_type',
        'scheme_type',
        'claim_type',
        'operation_type',
        'movement_type',
        'tracking_type',
        'status',
        'stage',
        'gender',
        'msme_category',
        'due_date_basis',
        'billing_mode',
        'opening_balance_type',
        'allocation_basis',
        'allocation_method',
        'role',
        'roles',
        'permission',
        'permissions',
        // Freeform sentences/paragraphs
        'description',
        'notes',
        'remarks',
        'narration',
        'purpose',
        'reason',
        'specification',
        'condition',
        'terms_and_conditions',
        'purchase_terms',
        'sales_terms',
        'selling_terms_and_conditions',
        'purchase_terms_and_conditions',
        'settlement_notes'
    ]);

    const EXCLUDED_TYPES = new Set([
        'password',
        'email',
        'url',
        'number',
        'date',
        'datetime-local',
        'time',
        'month',
        'week',
        'checkbox',
        'radio',
        'file',
        'hidden',
        'range',
        'color',
        'submit',
        'reset',
        'button',
        'image',
        'search'
    ]);

    /**
     * Determine whether an input element represents an applicable human-readable text field.
     */
    function isApplicableElement(el) {
        if (!el || (el.tagName !== 'INPUT' && el.tagName !== 'TEXTAREA')) {
            return false;
        }

        if (el.readOnly || el.disabled) {
            return false;
        }

        if (EXCLUDED_TYPES.has(el.type) && el.dataset.titleCase !== 'true') {
            return false;
        }

        // Explicit opt-out
        if (el.dataset.noTitleCase === 'true' || 
            el.hasAttribute('data-no-title-case') || 
            el.dataset.lowercase === 'true' || 
            el.dataset.noUppercase === 'true') {
            return false;
        }

        // Explicit opt-in
        if (el.dataset.titleCase === 'true' || el.dataset.isName === 'true') {
            return true;
        }

        const nameAttr = (el.getAttribute('name') || '').toLowerCase().trim();
        const idAttr = (el.getAttribute('id') || '').toLowerCase().trim();
        const classAttr = (el.getAttribute('class') || '').toLowerCase();
        const placeholder = (el.getAttribute('placeholder') || '').toLowerCase();

        // Avoid uppercase-styled code / statutory fields (PAN, GSTIN, etc.)
        if ((classAttr.includes('uppercase') || classAttr.includes('font-mono')) && 
            !nameAttr.includes('name') && !idAttr.includes('name') && !nameAttr.includes('label')) {
            return false;
        }

        // Search inputs
        if (placeholder.startsWith('search') || placeholder.includes('search by')) {
            return false;
        }

        // Extract clean field name: addresses[0][address_line_1] -> address_line_1
        let cleanName = nameAttr;
        const bracketMatch = nameAttr.match(/\[([^\]]+)\]$/);
        if (bracketMatch) {
            cleanName = bracketMatch[1];
        } else if (nameAttr.includes('.')) {
            const parts = nameAttr.split('.');
            cleanName = parts[parts.length - 1];
        }

        // 1. Strict Exclusions Check
        if (EXCLUDED_FIELDS.has(cleanName) || EXCLUDED_FIELDS.has(nameAttr) || EXCLUDED_FIELDS.has(idAttr)) {
            return false;
        }

        // Exclude foreign keys and IDs
        if (cleanName === 'id' || cleanName.endsWith('_id') || cleanName.endsWith('_ids')) {
            return false;
        }

        // Exclude technical identifiers, codes, numbers
        if (cleanName.endsWith('_code') || cleanName.endsWith('_no') || cleanName.endsWith('_number')) {
            return false;
        }

        // Exclude enum type slugs (e.g. party_type, cheque_type, account_type, discount_type) except descriptive types
        if (!['company_type', 'product_type', 'expense_type', 'leave_type'].includes(cleanName) && (cleanName.endsWith('_type') || cleanName === 'type')) {
            return false;
        }

        // Exclude statutory identifiers
        if (cleanName.includes('gstin') || cleanName.includes('ifsc') || 
            ['pan', 'cin', 'tan', 'sku', 'hsn'].includes(cleanName)) {
            return false;
        }

        // Exclude email & password
        if (cleanName.includes('email') || cleanName.includes('password') || 
            idAttr.includes('email') || idAttr.includes('password')) {
            return false;
        }

        // Exclude dates and timestamps
        if (cleanName.endsWith('_date') || cleanName.endsWith('_at') || cleanName.endsWith('_on') || 
            cleanName.endsWith('_from') || cleanName.endsWith('_to')) {
            return false;
        }

        // Exclude amounts, prices, quantities, percentages
        if (cleanName.endsWith('_amount') || cleanName.endsWith('_price') || cleanName.endsWith('_rate') || 
            cleanName.endsWith('_cost') || cleanName.endsWith('_qty') || cleanName.endsWith('_percent') || 
            cleanName.endsWith('_percentage') || cleanName.endsWith('_total') || cleanName.endsWith('_value') || 
            cleanName.endsWith('_days')) {
            return false;
        }

        return true;
    }

    /**
     * Capitalize the first letter of each word while typing without trimming or erasing spaces.
     */
    function formatWhileTyping(text) {
        if (!text || typeof text !== 'string') return '';
        if (!/[a-z]/.test(text)) return text;
        if (text.includes('://') || (text.includes('@') && !text.includes(' '))) return text;

        // Capitalize first letter after start, space, slash, hyphen, ampersand, or bracket
        return text.replace(/(^|[\s/&([{"'-])([a-z])/g, function (_, boundary, char) {
            return boundary + char.toUpperCase();
        }).replace(/([A-Za-z])'([A-Za-z]+)/g, function (_, pre, post) {
            if (post.toLowerCase() === 's') return pre + "'s";
            return pre + "'" + post.charAt(0).toUpperCase() + post.slice(1);
        });
    }

    /**
     * Format a string into full Title Case while normalizing spaces and preserving acronyms.
     */
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

            // 4. Preserve intentional CamelCase / PascalCase brand names (e.g., OmniCore, TitanFlow, ThinkPad, YouTube)
            if (/^[A-Z][a-z]+[A-Z]/.test(token)) {
                return token;
            }

            // 5. Handle apostrophes (e.g. supplier's, o'connor)
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

            // 6. Handle compound tokens separated by slash, hyphen, or ampersand
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

            // 7. Default Title Case
            return lower.charAt(0).toUpperCase() + lower.slice(1);
        });
    }

    // Expose globally
    window.dmsToTitleCase = toTitleCase;
    window.dmsFormatWhileTyping = formatWhileTyping;
    window.dmsIsApplicableField = isApplicableElement;

    /**
     * Format an element's value with Title Case.
     */
    function formatInputElement(el, fullFormat) {
        if (!el || typeof el.value !== 'string') return;
        const raw = el.value;
        if (!raw) return;

        const formatted = fullFormat ? toTitleCase(raw) : formatWhileTyping(raw);
        if (formatted !== raw) {
            const start = el.selectionStart;
            const end = el.selectionEnd;
            el.value = formatted;
            if (start !== null && end !== null && document.activeElement === el) {
                el.setSelectionRange(start, end);
            }
            if (fullFormat) {
                el.dispatchEvent(new Event('input', { bubbles: true }));
                el.dispatchEvent(new Event('change', { bubbles: true }));
            }
        }
    }

    // 1. Delegated input listener (runs while typing in real time)
    document.addEventListener('input', function (e) {
        const target = e.target;
        if (!isApplicableElement(target)) return;
        if (e.isComposing) return;

        const raw = target.value;
        if (!raw || typeof raw !== 'string') return;

        const formatted = formatWhileTyping(raw);
        if (formatted !== raw) {
            const start = target.selectionStart;
            const end = target.selectionEnd;
            target.value = formatted;
            if (start !== null && end !== null) {
                target.setSelectionRange(start, end);
            }
        }
    }, true);

    // 2. Delegated blur listener (formats field when user leaves, normalizing whitespace & acronyms)
    document.addEventListener('blur', function (e) {
        const target = e.target;
        if (isApplicableElement(target)) {
            formatInputElement(target, true);
        }
    }, true);

    // 3. Delegated paste event listener
    document.addEventListener('paste', function (e) {
        const target = e.target;
        if (isApplicableElement(target)) {
            setTimeout(function () {
                formatInputElement(target, true);
            }, 10);
        }
    }, true);

    // 4. Delegated form submit listener (guarantees all applicable fields are in Title Case before payload leaves)
    document.addEventListener('submit', function (e) {
        const form = e.target;
        if (form && form.querySelectorAll) {
            const inputs = form.querySelectorAll('input:not([type="password"]):not([type="hidden"]):not([type="file"]), textarea');
            inputs.forEach(function (input) {
                if (isApplicableElement(input)) {
                    formatInputElement(input, true);
                }
            });
        }
    }, true);

    // 5. Mark existing inputs and format pre-filled names if necessary
    function markAndFormatElement(el) {
        if (isApplicableElement(el)) {
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

    // 6. MutationObserver for dynamic modals, Alpine components, quick-add, and dynamically added rows
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
