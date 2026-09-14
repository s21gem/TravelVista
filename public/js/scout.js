(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {

        const table = document.getElementById('requestTable');

        if (table) {
            table.addEventListener('click', async function (event) {
                const button = event.target.closest('[data-withdraw]');
                if (!button) {
                    return;
                }

                const row = button.closest('tr');
                const requestId = parseInt(button.dataset.requestId, 10);
                const title = button.dataset.title || 'this request';

                if (!TV.confirm('Withdraw "' + title + '"? The editors will no longer see it.')) {
                    return;
                }

                button.classList.add('is-busy');
                row.classList.add('is-leaving');

                try {
                    const result = await TV.api('api/scout/requests/' + requestId, {
                        method: 'DELETE',
                        body: {}
                    });

                    row.remove();
                    updateCounts(result.counts);
                    TV.toast('success', result.message);

                    if (table.tBodies[0].children.length === 0) {
                        showEmpty();
                    }
                } catch (error) {
                    row.classList.remove('is-leaving');
                    button.classList.remove('is-busy');
                    TV.toast('error', error.message);
                }
            });
        }

        function updateCounts(counts) {
            if (!counts) {
                return;
            }
            Object.keys(counts).forEach(function (key) {
                document.querySelectorAll('[data-count="' + key + '"]').forEach(function (el) {
                    el.textContent = counts[key];
                });
            });
        }

        function showEmpty() {
            const panel = table.closest('.panel');
            panel.innerHTML = '<div class="empty"><h3>Nothing filed yet</h3>'
                + '<p>Write up a place you know and send it to the editors.</p>'
                + '<a class="btn btn--primary" href="' + TV.url('?page=scout/request_form') + '">'
                + 'File a dispatch</a></div>';
        }

        // the checkboxes are a shortcut into travel_medium_info; typing still works

        const mediumField = document.getElementById('travelMedium');
        const mediumBoxes = document.querySelectorAll('[data-medium]');

        if (mediumField && mediumBoxes.length) {
            let typedByHand = mediumField.value.trim() !== '' && !mediumField.dataset.fromBoxes;

            mediumBoxes.forEach(function (box) {
                if (mediumField.value.toLowerCase().indexOf(box.value.toLowerCase()) !== -1) {
                    box.checked = true;
                }

                box.addEventListener('change', function () {
                    const picked = [];
                    mediumBoxes.forEach(function (b) {
                        if (b.checked) {
                            picked.push(b.value);
                        }
                    });

                    if (typedByHand && !TV.confirm('Replace what you typed with the ticked options?')) {
                        box.checked = !box.checked;
                        return;
                    }

                    typedByHand = false;
                    mediumField.value = picked.join(', ');
                    mediumField.dataset.fromBoxes = '1';
                    mediumField.dispatchEvent(new Event('input'));
                });
            });

            mediumField.addEventListener('input', function () {
                if (!mediumField.dataset.fromBoxes) {
                    typedByHand = true;
                }
                delete mediumField.dataset.fromBoxes;
            });
        }
    });
})();
