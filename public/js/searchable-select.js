/**
 * DMS Pro — Universal Searchable Select Engine
 * Automatically upgrades all dropdowns (<select>) across the DMS project
 * into searchable, accessible, keyboard-navigable, zero-clipping dropdowns.
 */
(function () {
    'use strict';

    // Prevent duplicate script execution
    if (window.__DMS_SEARCHABLE_SELECT_LOADED__) return;
    window.__DMS_SEARCHABLE_SELECT_LOADED__ = true;

    // Inject styles for the searchable select component
    const styleEl = document.createElement('style');
    styleEl.id = 'dms-searchable-select-styles';
    styleEl.textContent = `
        .dms-searchable-container {
            position: relative;
            display: inline-block;
            vertical-align: middle;
        }
        .dms-searchable-trigger {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
            min-height: 38px;
            padding: 0.5rem 0.75rem;
            font-size: 0.875rem;
            line-height: 1.25rem;
            color: #1e293b;
            background-color: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 0.5rem;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            text-align: left;
            cursor: pointer;
            transition: all 0.15s ease-in-out;
            user-select: none;
        }
        .dms-searchable-trigger:hover:not(:disabled) {
            border-color: #94a3b8;
        }
        .dms-searchable-trigger:focus-visible,
        .dms-searchable-trigger.is-active {
            outline: none;
            border-color: #6366f1;
            box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.25);
        }
        .dms-searchable-trigger.has-error {
            border-color: #ef4444 !important;
            box-shadow: 0 0 0 2px rgba(239, 68, 68, 0.2) !important;
        }
        .dms-searchable-trigger:disabled {
            background-color: #f8fafc;
            color: #94a3b8;
            border-color: #e2e8f0;
            cursor: not-allowed;
            box-shadow: none;
        }
        .dms-searchable-trigger-text {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            flex: 1 1 0%;
            margin-right: 0.5rem;
        }
        .dms-searchable-trigger-text.is-placeholder {
            color: #94a3b8;
            font-weight: 400;
        }
        .dms-searchable-trigger-actions {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            flex-shrink: 0;
        }
        .dms-searchable-clear-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 1.25rem;
            height: 1.25rem;
            padding: 0;
            color: #94a3b8;
            border-radius: 0.25rem;
            transition: color 0.15s, background-color 0.15s;
        }
        .dms-searchable-clear-btn:hover {
            color: #475569;
            background-color: #f1f5f9;
        }
        .dms-searchable-chevron {
            width: 1rem;
            height: 1rem;
            color: #94a3b8;
            transition: transform 0.2s ease-in-out;
        }
        .dms-searchable-trigger.is-active .dms-searchable-chevron {
            transform: rotate(180deg);
            color: #6366f1;
        }

        /* Fixed Popover Menu */
        .dms-searchable-menu {
            position: fixed;
            z-index: 99999;
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 0.625rem;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            animation: dmsSearchableFadeIn 0.12s ease-out;
            font-family: inherit;
        }
        @keyframes dmsSearchableFadeIn {
            from { opacity: 0; transform: scale(0.98); }
            to { opacity: 1; transform: scale(1); }
        }
        .dms-searchable-search-wrapper {
            padding: 0.5rem;
            background-color: #f8fafc;
            border-bottom: 1px solid #f1f5f9;
            position: relative;
        }
        .dms-searchable-search-input {
            width: 100%;
            padding: 0.375rem 0.625rem 0.375rem 2rem;
            font-size: 0.8125rem;
            line-height: 1.125rem;
            color: #0f172a;
            background-color: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 0.375rem;
            outline: none;
            transition: all 0.15s;
        }
        .dms-searchable-search-input:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.15);
        }
        .dms-searchable-search-icon {
            position: absolute;
            left: 1.125rem;
            top: 50%;
            transform: translateY(-50%);
            width: 0.875rem;
            height: 0.875rem;
            color: #94a3b8;
            pointer-events: none;
        }
        .dms-searchable-options-list {
            max-height: 230px;
            overflow-y: auto;
            padding: 0.25rem;
            overscroll-behavior: contain;
        }
        .dms-searchable-options-list::-webkit-scrollbar {
            width: 6px;
        }
        .dms-searchable-options-list::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 4px;
        }
        .dms-searchable-options-list::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }
        .dms-searchable-options-list::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
        .dms-searchable-option {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.4375rem 0.625rem;
            font-size: 0.8125rem;
            line-height: 1.125rem;
            color: #334155;
            border-radius: 0.375rem;
            cursor: pointer;
            transition: background-color 0.1s, color 0.1s;
        }
        .dms-searchable-option:hover,
        .dms-searchable-option.is-highlighted {
            background-color: #f1f5f9;
            color: #0f172a;
        }
        .dms-searchable-option.is-selected {
            background-color: #eef2ff;
            color: #4338ca;
            font-weight: 600;
        }
        .dms-searchable-option.is-disabled {
            opacity: 0.45;
            cursor: not-allowed;
            pointer-events: none;
        }
        .dms-searchable-option-text {
            flex: 1 1 0%;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .dms-searchable-option-code {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 0.6875rem;
            color: #94a3b8;
            margin-left: 0.5rem;
            flex-shrink: 0;
            background: #f8fafc;
            padding: 0.125rem 0.375rem;
            border-radius: 0.25rem;
            border: 1px solid #e2e8f0;
        }
        .dms-searchable-option-check {
            width: 0.875rem;
            height: 0.875rem;
            margin-left: 0.5rem;
            color: #4f46e5;
            flex-shrink: 0;
        }
        .dms-searchable-empty {
            padding: 1.25rem 0.75rem;
            text-align: center;
            font-size: 0.8125rem;
            color: #94a3b8;
            font-style: italic;
        }
    `;
    document.head.appendChild(styleEl);

    // Active state manager for single open menu
    let activeDropdown = null;

    /**
     * Intercept HTMLSelectElement.prototype.value and selectedIndex
     * so programmatic changes (Alpine, external JS, Livewire) update the custom UI.
     */
    const proto = HTMLSelectElement.prototype;
    const origValueDescriptor = Object.getOwnPropertyDescriptor(proto, 'value');
    if (origValueDescriptor && origValueDescriptor.set) {
        Object.defineProperty(proto, 'value', {
            get() {
                return origValueDescriptor.get.call(this);
            },
            set(val) {
                const oldVal = origValueDescriptor.get.call(this);
                origValueDescriptor.set.call(this, val);
                if (oldVal !== val && this.hasAttribute('data-dms-searchable-initialized')) {
                    this.dispatchEvent(new CustomEvent('dms:select-value-updated', { bubbles: false }));
                }
            }
        });
    }

    const origIndexDescriptor = Object.getOwnPropertyDescriptor(proto, 'selectedIndex');
    if (origIndexDescriptor && origIndexDescriptor.set) {
        Object.defineProperty(proto, 'selectedIndex', {
            get() {
                return origIndexDescriptor.get.call(this);
            },
            set(idx) {
                const oldIdx = origIndexDescriptor.get.call(this);
                origIndexDescriptor.set.call(this, idx);
                if (oldIdx !== idx && this.hasAttribute('data-dms-searchable-initialized')) {
                    this.dispatchEvent(new CustomEvent('dms:select-value-updated', { bubbles: false }));
                }
            }
        });
    }

    /**
     * Enhance a single <select> element into a searchable select
     */
    function enhance(select) {
        if (!select || select.nodeType !== 1 || select.tagName !== 'SELECT') return;

        // Skip multi-selects
        if (select.multiple) return;

        // Skip explicitly disabled selects
        if (select.hasAttribute('data-no-search') || select.getAttribute('data-searchable') === 'false') return;

        // Skip already initialized
        if (select.hasAttribute('data-dms-searchable-initialized')) return;

        // Skip elements inside <template>
        if (select.closest('template')) return;

        // Skip selects that are intentionally hidden by existing custom pickers
        // (e.g. po-product with class="hidden" or style display: none)
        const isExplicitlyHidden = select.classList.contains('hidden') ||
            select.style.display === 'none' ||
            select.getAttribute('hidden') !== null;
        if (isExplicitlyHidden && !select.hasAttribute('data-force-searchable')) {
            return;
        }

        select.setAttribute('data-dms-searchable-initialized', 'true');

        // Create Container
        const container = document.createElement('div');
        container.className = 'dms-searchable-container';

        // Mirror sizing and layout classes from the original select
        const widthClasses = [];
        select.classList.forEach(cls => {
            if (/^(w-|max-w-|min-w-|flex-|col-)/.test(cls)) {
                widthClasses.push(cls);
            }
        });
        if (widthClasses.length > 0) {
            container.classList.add(...widthClasses);
        } else if (select.classList.contains('block') || select.classList.contains('w-full') || !select.classList.contains('inline')) {
            container.classList.add('w-full');
        }

        // Apply explicit inline width if present
        if (select.style.width) {
            container.style.width = select.style.width;
        }

        // Insert container right before select, then move select inside container
        select.parentNode.insertBefore(container, select);
        container.appendChild(select);

        // Hide the original select while maintaining full form & validation semantics
        select.style.cssText = 'position: absolute !important; opacity: 0 !important; width: 1px !important; height: 1px !important; margin: 0 !important; padding: 0 !important; border: 0 !important; pointer-events: none !important; z-index: -1 !important;';
        select.tabIndex = -1;

        // Create Trigger Button
        const trigger = document.createElement('button');
        trigger.type = 'button';
        trigger.className = 'dms-searchable-trigger';
        if (select.id) {
            trigger.id = `${select.id}_searchable_trigger`;
        }

        // Mirror disabled state
        if (select.disabled) {
            trigger.disabled = true;
        }

        // Check initial error state
        if (select.classList.contains('border-red-300') || select.classList.contains('border-red-500') || select.classList.contains('is-invalid')) {
            trigger.classList.add('has-error');
        }

        const labelSpan = document.createElement('span');
        labelSpan.className = 'dms-searchable-trigger-text';

        const actionsSpan = document.createElement('span');
        actionsSpan.className = 'dms-searchable-trigger-actions';

        // Clear button (✕)
        const clearBtn = document.createElement('span');
        clearBtn.className = 'dms-searchable-clear-btn';
        clearBtn.title = 'Clear selection';
        clearBtn.style.display = 'none';
        clearBtn.innerHTML = `
            <svg class="w-3.5 h-3.5" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
            </svg>
        `;

        // Chevron icon
        const chevron = document.createElement('span');
        chevron.className = 'dms-searchable-chevron';
        chevron.innerHTML = `
            <svg viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd"/>
            </svg>
        `;

        actionsSpan.appendChild(clearBtn);
        actionsSpan.appendChild(chevron);
        trigger.appendChild(labelSpan);
        trigger.appendChild(actionsSpan);
        container.appendChild(trigger);

        // Associated label click support
        if (select.id) {
            const associatedLabel = document.querySelector(`label[for="${select.id}"]`);
            if (associatedLabel) {
                associatedLabel.addEventListener('click', (e) => {
                    e.preventDefault();
                    trigger.focus();
                });
            }
        }

        // Cache options data
        let optionsData = [];
        let highlightedIndex = -1;
        let menuEl = null;
        let searchInput = null;
        let optionsListEl = null;
        let emptyEl = null;

        function readOptions() {
            optionsData = [];
            const optionNodes = Array.from(select.querySelectorAll('option'));
            optionNodes.forEach((opt, idx) => {
                optionsData.push({
                    index: idx,
                    value: opt.value,
                    text: opt.textContent.trim(),
                    code: opt.dataset.code || null,
                    subtext: opt.dataset.subtext || null,
                    disabled: opt.disabled,
                    selected: opt.selected,
                    isPlaceholder: opt.value === '' && (idx === 0 || opt.textContent.toLowerCase().includes('select') || opt.textContent.toLowerCase().includes('choose') || opt.textContent.trim() === '—' || opt.textContent.trim() === '')
                });
            });
        }

        function updateTriggerDisplay() {
            readOptions();
            const selectedOpt = optionsData.find(o => String(o.value) === String(select.value)) ||
                                optionsData.find(o => o.selected) ||
                                optionsData[0];

            if (selectedOpt && selectedOpt.value !== '') {
                labelSpan.textContent = selectedOpt.text;
                labelSpan.classList.remove('is-placeholder');

                // Check if can be cleared
                const hasEmptyOption = optionsData.some(o => o.value === '');
                const canClear = !select.required || hasEmptyOption;
                clearBtn.style.display = canClear ? 'inline-flex' : 'none';
            } else {
                const placeholder = select.getAttribute('placeholder') ||
                                    (selectedOpt ? selectedOpt.text : 'Select an option');
                labelSpan.textContent = placeholder || 'Select an option';
                labelSpan.classList.add('is-placeholder');
                clearBtn.style.display = 'none';
            }

            if (trigger.classList.contains('has-error') && select.value !== '') {
                trigger.classList.remove('has-error');
            }
        }

        // Create Dropdown Menu DOM
        function createMenu() {
            if (menuEl) return;

            menuEl = document.createElement('div');
            menuEl.className = 'dms-searchable-menu';
            menuEl.style.display = 'none';

            // Search Header
            const searchWrapper = document.createElement('div');
            searchWrapper.className = 'dms-searchable-search-wrapper';

            const searchIcon = document.createElement('div');
            searchIcon.className = 'dms-searchable-search-icon';
            searchIcon.innerHTML = `
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M16.65 11a5.65 5.65 0 11-11.3 0 5.65 5.65 0 0111.3 0z"/>
                </svg>
            `;

            searchInput = document.createElement('input');
            searchInput.type = 'text';
            searchInput.className = 'dms-searchable-search-input';
            searchInput.placeholder = 'Type to search...';
            searchInput.autocomplete = 'off';
            searchInput.spellcheck = false;

            searchWrapper.appendChild(searchIcon);
            searchWrapper.appendChild(searchInput);
            menuEl.appendChild(searchWrapper);

            // Options List
            optionsListEl = document.createElement('div');
            optionsListEl.className = 'dms-searchable-options-list';
            menuEl.appendChild(optionsListEl);

            // Empty state
            emptyEl = document.createElement('div');
            emptyEl.className = 'dms-searchable-empty';
            emptyEl.textContent = 'No matching options';
            emptyEl.style.display = 'none';
            menuEl.appendChild(emptyEl);

            // Mount to body to prevent any clipping from parent modals or tables
            document.body.appendChild(menuEl);

            // Search input events
            searchInput.addEventListener('input', () => {
                filterOptions(searchInput.value);
            });

            searchInput.addEventListener('keydown', handleKeydown);
        }

        // Position Dropdown relative to Trigger
        function positionMenu() {
            if (!menuEl || menuEl.style.display === 'none') return;

            const rect = trigger.getBoundingClientRect();
            const viewportHeight = window.innerHeight;
            const viewportWidth = window.innerWidth;
            const minMenuWidth = Math.max(rect.width, 220);

            menuEl.style.width = `${minMenuWidth}px`;

            // Calculate vertical positioning
            const spaceBelow = viewportHeight - rect.bottom;
            const spaceAbove = rect.top;
            const maxExpectedHeight = 280;

            if (spaceBelow < 200 && spaceAbove > spaceBelow) {
                // Open upwards
                menuEl.style.top = 'auto';
                menuEl.style.bottom = `${viewportHeight - rect.top + 4}px`;
            } else {
                // Open downwards
                menuEl.style.top = `${rect.bottom + 4}px`;
                menuEl.style.bottom = 'auto';
            }

            // Calculate horizontal positioning
            let left = rect.left;
            if (left + minMenuWidth > viewportWidth - 8) {
                left = viewportWidth - minMenuWidth - 8;
            }
            if (left < 8) left = 8;

            menuEl.style.left = `${left}px`;
        }

        function renderOptions(filtered) {
            optionsListEl.innerHTML = '';
            highlightedIndex = -1;

            if (filtered.length === 0) {
                emptyEl.style.display = 'block';
                return;
            }
            emptyEl.style.display = 'none';

            const currentVal = String(select.value);

            filtered.forEach((opt, idx) => {
                const optEl = document.createElement('div');
                optEl.className = 'dms-searchable-option';
                optEl.dataset.index = idx;
                optEl.dataset.value = opt.value;

                if (String(opt.value) === currentVal) {
                    optEl.classList.add('is-selected');
                }
                if (opt.disabled) {
                    optEl.classList.add('is-disabled');
                }

                const textSpan = document.createElement('span');
                textSpan.className = 'dms-searchable-option-text';
                textSpan.textContent = opt.text || '—';
                optEl.appendChild(textSpan);

                if (opt.code) {
                    const codeSpan = document.createElement('span');
                    codeSpan.className = 'dms-searchable-option-code';
                    codeSpan.textContent = opt.code;
                    optEl.appendChild(codeSpan);
                }

                if (String(opt.value) === currentVal) {
                    const checkSpan = document.createElement('span');
                    checkSpan.className = 'dms-searchable-option-check';
                    checkSpan.innerHTML = `
                        <svg viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                    `;
                    optEl.appendChild(checkSpan);
                }

                optEl.addEventListener('mouseenter', () => {
                    setHighlighted(idx);
                });

                optEl.addEventListener('click', (e) => {
                    e.stopPropagation();
                    selectValue(opt.value);
                });

                optionsListEl.appendChild(optEl);
            });

            // Auto-highlight selected or first non-disabled option
            const selectedIdx = filtered.findIndex(o => String(o.value) === currentVal);
            if (selectedIdx >= 0) {
                setHighlighted(selectedIdx, false);
            } else if (filtered.length > 0) {
                setHighlighted(0, false);
            }
        }

        let currentFiltered = [];

        function filterOptions(query) {
            readOptions();
            const q = (query || '').toLowerCase().trim();
            if (!q) {
                currentFiltered = [...optionsData];
            } else {
                const terms = q.split(/\s+/).filter(Boolean);
                currentFiltered = optionsData.filter(opt => {
                    const haystack = `${opt.text} ${opt.code || ''} ${opt.subtext || ''} ${opt.value}`.toLowerCase();
                    return terms.every(term => haystack.includes(term));
                });
            }
            renderOptions(currentFiltered);
        }

        function setHighlighted(idx, scrollIntoView = true) {
            const items = optionsListEl.querySelectorAll('.dms-searchable-option');
            items.forEach(el => el.classList.remove('is-highlighted'));

            if (idx >= 0 && idx < items.length) {
                highlightedIndex = idx;
                const activeEl = items[idx];
                activeEl.classList.add('is-highlighted');
                if (scrollIntoView) {
                    activeEl.scrollIntoView({ block: 'nearest' });
                }
            } else {
                highlightedIndex = -1;
            }
        }

        function selectValue(val) {
            select.value = val;
            select.dispatchEvent(new Event('input', { bubbles: true }));
            select.dispatchEvent(new Event('change', { bubbles: true }));
            updateTriggerDisplay();
            closeDropdown();
            trigger.focus();
        }

        function handleKeydown(e) {
            const items = optionsListEl.querySelectorAll('.dms-searchable-option:not(.is-disabled)');
            if (items.length === 0 && e.key !== 'Escape' && e.key !== 'Tab') return;

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                let next = highlightedIndex + 1;
                if (next >= items.length) next = 0;
                setHighlighted(next);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                let prev = highlightedIndex - 1;
                if (prev < 0) prev = items.length - 1;
                setHighlighted(prev);
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (highlightedIndex >= 0 && highlightedIndex < items.length) {
                    const item = items[highlightedIndex];
                    selectValue(item.dataset.value);
                }
            } else if (e.key === 'Escape') {
                e.preventDefault();
                closeDropdown();
                trigger.focus();
            } else if (e.key === 'Tab') {
                closeDropdown();
            }
        }

        function openDropdown() {
            if (select.disabled) return;
            if (activeDropdown && activeDropdown !== closeDropdown) {
                activeDropdown();
            }

            createMenu();
            trigger.classList.add('is-active');
            menuEl.style.display = 'flex';
            searchInput.value = '';
            filterOptions('');
            positionMenu();

            setTimeout(() => {
                if (searchInput) {
                    searchInput.focus();
                }
            }, 20);

            activeDropdown = closeDropdown;
        }

        function closeDropdown() {
            if (!menuEl || menuEl.style.display === 'none') return;
            menuEl.style.display = 'none';
            trigger.classList.remove('is-active');
            if (activeDropdown === closeDropdown) {
                activeDropdown = null;
            }
        }

        function toggleDropdown() {
            if (menuEl && menuEl.style.display === 'flex') {
                closeDropdown();
            } else {
                openDropdown();
            }
        }

        // Trigger Click & Keydown
        trigger.addEventListener('click', (e) => {
            e.preventDefault();
            toggleDropdown();
        });

        trigger.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ' || e.key === 'ArrowDown') {
                e.preventDefault();
                openDropdown();
            }
        });

        // Clear button click
        clearBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            selectValue('');
        });

        // Listen for programmatic value updates or form events
        select.addEventListener('dms:select-value-updated', updateTriggerDisplay);
        select.addEventListener('change', updateTriggerDisplay);
        select.addEventListener('input', updateTriggerDisplay);

        if (select.form) {
            select.form.addEventListener('reset', () => {
                setTimeout(updateTriggerDisplay, 10);
            });
        }

        // HTML5 Validation: Handle native 'invalid' event
        select.addEventListener('invalid', () => {
            trigger.classList.add('has-error');
            trigger.focus();
        });

        // Observe changes to <select> children (options dynamically loaded/changed)
        const observer = new MutationObserver((mutations) => {
            let optionsChanged = false;
            let attrChanged = false;
            for (const m of mutations) {
                if (m.type === 'childList') {
                    optionsChanged = true;
                } else if (m.type === 'attributes') {
                    if (m.attributeName === 'disabled') {
                        trigger.disabled = select.disabled;
                    }
                    if (m.attributeName === 'class') {
                        if (select.classList.contains('border-red-300') || select.classList.contains('border-red-500')) {
                            trigger.classList.add('has-error');
                        } else {
                            trigger.classList.remove('has-error');
                        }
                    }
                    attrChanged = true;
                }
            }
            if (optionsChanged) {
                updateTriggerDisplay();
                if (menuEl && menuEl.style.display === 'flex') {
                    filterOptions(searchInput.value);
                    positionMenu();
                }
            }
        });
        observer.observe(select, { childList: true, subtree: true, attributes: true });

        // Initial paint
        updateTriggerDisplay();
    }

    /**
     * Enhance all eligible selects inside a given root node
     */
    function enhanceAll(root) {
        const context = root || document;
        const selects = context.querySelectorAll('select');
        selects.forEach(enhance);
    }

    // Global click listener to close open dropdown when clicking outside
    document.addEventListener('pointerdown', (e) => {
        if (!activeDropdown) return;
        const target = e.target;
        if (target.closest('.dms-searchable-container') || target.closest('.dms-searchable-menu')) {
            return;
        }
        activeDropdown();
    });

    // Reposition open menu on scroll/resize
    window.addEventListener('resize', () => {
        if (activeDropdown && document.querySelector('.dms-searchable-menu[style*="display: flex"]')) {
            const trigger = document.querySelector('.dms-searchable-trigger.is-active');
            if (trigger) {
                trigger.dispatchEvent(new CustomEvent('dms:reposition'));
            }
        }
    });

    window.addEventListener('scroll', () => {
        if (activeDropdown) {
            // Close dropdown if user scrolls the page significantly
            // or reposition smoothly
            const openMenu = document.querySelector('.dms-searchable-menu:not([style*="display: none"])');
            const activeTrigger = document.querySelector('.dms-searchable-trigger.is-active');
            if (openMenu && activeTrigger) {
                const rect = activeTrigger.getBoundingClientRect();
                if (rect.top < -50 || rect.bottom > window.innerHeight + 50) {
                    activeDropdown();
                }
            }
        }
    }, true);

    // Global MutationObserver to automatically enhance newly added selects
    const globalObserver = new MutationObserver((mutations) => {
        for (const m of mutations) {
            for (const node of m.addedNodes) {
                if (node.nodeType === 1) { // ELEMENT_NODE
                    if (node.tagName === 'SELECT') {
                        enhance(node);
                    } else if (node.querySelectorAll) {
                        enhanceAll(node);
                    }
                }
            }
        }
    });

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            enhanceAll(document.body);
            globalObserver.observe(document.body, { childList: true, subtree: true });
        });
    } else {
        enhanceAll(document.body);
        globalObserver.observe(document.body, { childList: true, subtree: true });
    }

    // Expose Global Helper
    window.DmsSearchableSelect = {
        enhance,
        enhanceAll,
        closeActive() {
            if (activeDropdown) activeDropdown();
        }
    };
})();

