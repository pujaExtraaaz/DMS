<style>
/* Phone Directory Scoped Button System */
.btn-pd-primary {
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 0.375rem !important;
    background-color: #7c3aed !important; /* Solid Purple (Tailwind Violet-600) */
    color: #ffffff !important;
    border: 1px solid #6d28d9 !important;
    border-radius: 0.75rem !important;
    padding: 0.5rem 0.875rem !important;
    font-size: 0.75rem !important;
    font-weight: 600 !important;
    line-height: 1rem !important;
    text-decoration: none !important;
    cursor: pointer !important;
    box-shadow: 0 1px 3px 0 rgba(124, 58, 237, 0.25), 0 1px 2px -1px rgba(124, 58, 237, 0.25) !important;
    transition: all 0.15s ease-in-out !important;
    white-space: nowrap !important;
    min-height: 38px !important;
    opacity: 1 !important;
}
.btn-pd-primary:hover {
    background-color: #6d28d9 !important;
    border-color: #5b21b6 !important;
    color: #ffffff !important;
    box-shadow: 0 4px 6px -1px rgba(124, 58, 237, 0.3), 0 2px 4px -2px rgba(124, 58, 237, 0.3) !important;
}
.btn-pd-primary:active {
    background-color: #5b21b6 !important;
    border-color: #4c1d95 !important;
    color: #ffffff !important;
    transform: translateY(1px);
}
.btn-pd-primary:focus,
.btn-pd-primary:focus-visible {
    outline: none !important;
    box-shadow: 0 0 0 2px #ffffff, 0 0 0 4px #7c3aed !important;
}
.btn-pd-primary svg {
    color: #ffffff !important;
    stroke: currentColor;
    flex-shrink: 0;
}

.btn-pd-secondary {
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 0.375rem !important;
    background-color: #ffffff !important;
    color: #0f172a !important; /* Dark Navy text */
    border: 1px solid #cbd5e1 !important; /* Visible Slate-300 border */
    border-radius: 0.75rem !important;
    padding: 0.5rem 0.875rem !important;
    font-size: 0.75rem !important;
    font-weight: 600 !important;
    line-height: 1rem !important;
    text-decoration: none !important;
    cursor: pointer !important;
    box-shadow: 0 1px 2px 0 rgba(15, 23, 42, 0.05) !important;
    transition: all 0.15s ease-in-out !important;
    white-space: nowrap !important;
    min-height: 38px !important;
    opacity: 1 !important;
}
.btn-pd-secondary:hover {
    background-color: #f8fafc !important;
    border-color: #94a3b8 !important;
    color: #0f172a !important;
}
.btn-pd-secondary:active {
    background-color: #f1f5f9 !important;
    border-color: #64748b !important;
    color: #0f172a !important;
    transform: translateY(1px);
}
.btn-pd-secondary:focus,
.btn-pd-secondary:focus-visible {
    outline: none !important;
    box-shadow: 0 0 0 2px #ffffff, 0 0 0 4px #94a3b8 !important;
}
.btn-pd-secondary svg {
    color: #0f172a !important;
    stroke: currentColor;
    flex-shrink: 0;
}
.btn-pd-secondary svg.text-amber-500 {
    color: #f59e0b !important;
    stroke: currentColor;
}
.btn-pd-secondary.is-active-important {
    background-color: #fffbeb !important;
    border-color: #f59e0b !important;
    color: #78350f !important;
}
.btn-pd-secondary.is-active-important svg {
    color: #f59e0b !important;
    fill: #f59e0b !important;
}
.btn-pd-secondary.is-active-filter {
    background-color: #f5f3ff !important;
    border-color: #a78bfa !important;
    color: #4c1d95 !important;
}
.btn-pd-secondary.is-active-filter svg {
    color: #7c3aed !important;
}
</style>
