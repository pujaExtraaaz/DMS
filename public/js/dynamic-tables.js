/**
 * DMS Global Dynamic Search, Filter, and Sorting Handler
 * Provides seamless zero-reload dynamic updates across all listings.
 */
(function () {
    let currentController = null;
    let debounceTimer = null;

    function getContainer(target) {
        if (target) {
            const explicit = target.closest('[data-dynamic-container]') || target.closest('#listing-container');
            if (explicit) return explicit;
        }
        return document.querySelector('[data-dynamic-container]') || document.querySelector('#listing-container');
    }

    function buildUrlWithParams(container, overrides = {}) {
        const url = new URL(window.location.href);

        // Gather all dynamic inputs within or associated with the container
        const searchInput = container.querySelector('[data-dynamic-search]');
        if (searchInput) {
            const val = searchInput.value.trim();
            const name = searchInput.getAttribute('name') || 'search';
            if (val) {
                url.searchParams.set(name, val);
            } else {
                url.searchParams.delete(name);
                // Also clean up alternative name if present
                if (name === 'search') url.searchParams.delete('q');
                if (name === 'q') url.searchParams.delete('search');
            }
        }

        const filterInputs = container.querySelectorAll('[data-dynamic-filter]');
        filterInputs.forEach(input => {
            const name = input.getAttribute('name');
            if (!name) return;
            const val = input.value;
            if (val !== '' && val !== null && val !== undefined) {
                url.searchParams.set(name, val);
            } else {
                url.searchParams.delete(name);
            }
        });

        // Apply explicit overrides
        Object.keys(overrides).forEach(key => {
            const val = overrides[key];
            if (val === null || val === undefined || val === '') {
                url.searchParams.delete(key);
            } else {
                url.searchParams.set(key, val);
            }
        });

        return url.toString();
    }

    async function fetchAndSwap(targetUrl, pushState = false, container = null) {
        const targetContainer = container || getContainer();
        if (!targetContainer) return;

        // Cancel any pending request
        if (currentController) {
            currentController.abort();
        }
        currentController = new AbortController();

        // Visual loading feedback
        targetContainer.classList.add('opacity-60', 'pointer-events-none', 'transition-opacity', 'duration-200');

        try {
            const res = await fetch(targetUrl, {
                signal: currentController.signal,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-DMS-Dynamic': '1',
                },
            });

            if (!res.ok) {
                targetContainer.classList.remove('opacity-60', 'pointer-events-none');
                return;
            }

            const html = await res.text();
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');

            const containerId = targetContainer.id;
            let newContainer = null;

            if (containerId) {
                newContainer = doc.getElementById(containerId);
            }
            if (!newContainer) {
                newContainer = doc.querySelector('[data-dynamic-container]');
            }

            if (newContainer) {
                // Preserve active search input focus and cursor position if user is typing
                const activeEl = document.activeElement;
                const wasSearchFocused = activeEl && activeEl.hasAttribute('data-dynamic-search');
                const cursorStart = wasSearchFocused ? activeEl.selectionStart : null;
                const cursorEnd = wasSearchFocused ? activeEl.selectionEnd : null;

                targetContainer.innerHTML = newContainer.innerHTML;

                // Re-sync attributes
                Array.from(newContainer.attributes).forEach(attr => {
                    targetContainer.setAttribute(attr.name, attr.value);
                });

                if (pushState) {
                    window.history.pushState(null, '', targetUrl);
                } else {
                    window.history.replaceState(null, '', targetUrl);
                }

                // If user was typing in the search box, restore focus
                if (wasSearchFocused) {
                    const restoredInput = targetContainer.querySelector('[data-dynamic-search]');
                    if (restoredInput) {
                        restoredInput.focus();
                        if (cursorStart !== null && cursorEnd !== null) {
                            restoredInput.setSelectionRange(cursorStart, cursorEnd);
                        }
                    }
                }
            } else {
                // Fallback to normal navigation if structure differs
                window.location.href = targetUrl;
            }
        } catch (err) {
            if (err.name !== 'AbortError') {
                console.error('Dynamic table update failed:', err);
            }
        } finally {
            targetContainer.classList.remove('opacity-60', 'pointer-events-none');
        }
    }

    // Debounced search typing handler
    document.addEventListener('input', function (e) {
        const input = e.target.closest('[data-dynamic-search]');
        if (!input) return;

        const container = getContainer(input);
        if (!container) return;

        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            const newUrl = buildUrlWithParams(container, { page: null });
            fetchAndSwap(newUrl, false, container);
        }, 320);
    });

    // Instant filter select / date changes
    document.addEventListener('change', function (e) {
        const filter = e.target.closest('[data-dynamic-filter]');
        if (!filter) return;

        const container = getContainer(filter);
        if (!container) return;

        const newUrl = buildUrlWithParams(container, { page: null });
        fetchAndSwap(newUrl, false, container);
    });

    // Intercept clicks on sort headers, reset links, and pagination links
    document.addEventListener('click', function (e) {
        // Sort headers
        const sortLink = e.target.closest('[data-sort-link]');
        if (sortLink) {
            e.preventDefault();
            const container = getContainer(sortLink);
            fetchAndSwap(sortLink.href, false, container);
            return;
        }

        // Reset filter button
        const resetLink = e.target.closest('[data-reset-filters]');
        if (resetLink) {
            e.preventDefault();
            const container = getContainer(resetLink);
            fetchAndSwap(resetLink.href, false, container);
            return;
        }

        // Pagination links
        const paginationLink = e.target.closest('.pagination a') || e.target.closest('[rel="prev"]') || e.target.closest('[rel="next"]');
        if (paginationLink) {
            const container = getContainer(paginationLink);
            if (container) {
                e.preventDefault();
                fetchAndSwap(paginationLink.href, true, container);
            }
        }
    });

    // Handle browser forward / back navigation
    window.addEventListener('popstate', function () {
        const container = getContainer();
        if (container) {
            fetchAndSwap(window.location.href, false, container);
        }
    });
})();

