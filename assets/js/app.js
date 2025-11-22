const nav = document.querySelector('.top-nav nav');
const toggle = document.querySelector('.menu-toggle');

if (toggle && nav) {
    toggle.addEventListener('click', () => {
        nav.classList.toggle('open');
    });
}

function attachClearListingHandlers() {
    const message = 'Listing cleared.';
    document.querySelectorAll('[data-clear-target]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const selector = btn.dataset.clearTarget;
            if (!selector) {
                return;
            }
            const tables = Array.from(document.querySelectorAll(selector));
            tables.forEach((table) => {
                const rows = table.querySelectorAll('tbody tr');
                if (rows.length === 0) {
                    const columns = table.querySelector('thead tr')?.children.length || 1;
                    table.querySelector('tbody').innerHTML = `<tr><td colspan="${columns}">${message}</td></tr>`;
                    return;
                }
                rows.forEach((row, index) => {
                    if (index === 0) {
                        row.innerHTML = `<td colspan="${row.children.length || 1}">${message}</td>`;
                    } else {
                        row.remove();
                    }
                });
            });
        });
    });
}

document.addEventListener('DOMContentLoaded', attachClearListingHandlers);
