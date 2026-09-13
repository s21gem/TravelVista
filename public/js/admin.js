(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {

        document.addEventListener('click', async function (event) {
            const button = event.target.closest('[data-verify-toggle]');
            if (!button) {
                return;
            }

            const userId = parseInt(button.dataset.userId, 10);
            const makeVerified = button.dataset.verified !== '1';
            const row = button.closest('tr');

            button.classList.add('is-busy');

            try {
                const result = await TV.api('api/admin/verify-user', {
                    method: 'POST',
                    body: { user_id: userId, verified: makeVerified ? 1 : 0 }
                });

                if (button.hasAttribute('data-remove-on-verify') && result.verified) {
                    row.classList.add('is-leaving');
                    setTimeout(function () {
                        const tbody = row.parentNode;
                        row.remove();
                        if (tbody && tbody.children.length === 0) {
                            const panel = tbody.closest('.panel');
                            if (panel) {
                                panel.remove();
                            }
                        }
                    }, 180);
                } else {
                    button.dataset.verified = result.verified ? '1' : '0';
                    button.textContent = result.verified ? 'Unverify' : 'Verify';
                    button.className = 'btn btn--sm ' + (result.verified ? 'btn--ghost' : 'btn--go');

                    const badge = row.querySelector('[data-verify-badge]');
                    if (badge) {
                        badge.className = 'tag ' + (result.verified ? 'tag--approved' : 'tag--pending');
                        badge.textContent = result.verified ? 'Verified' : 'Pending';
                    }
                }

                setBadge('unverified', result.unverified);
                TV.toast('success', result.message);
            } catch (error) {
                TV.toast('error', error.message);
            } finally {
                button.classList.remove('is-busy');
            }
        });

        document.addEventListener('change', async function (event) {
            const box = event.target.closest('[data-verify-user]');
            if (!box) {
                return;
            }

            const userId = parseInt(box.dataset.verifyUser, 10);
            const wanted = box.checked;
            const row = box.closest('tr');

            box.disabled = true;

            try {
                const result = await TV.api('api/admin/verify-user', {
                    method: 'POST',
                    body: { user_id: userId, verified: wanted ? 1 : 0 }
                });

                box.checked = result.verified;

                const badge = row ? row.querySelector('[data-verify-badge]') : null;
                if (badge) {
                    badge.className = 'tag ' + (result.verified ? 'tag--approved' : 'tag--pending');
                    badge.textContent = result.verified ? 'Verified' : 'Pending';
                }

                setBadge('unverified', result.unverified);
                TV.toast('success', result.message);
            } catch (error) {
                box.checked = !wanted;
                TV.toast('error', error.message);
            } finally {
                box.disabled = false;
            }
        });

        document.addEventListener('click', async function (event) {
            const button = event.target.closest('[data-approve-request]');
            if (!button) {
                return;
            }

            const requestId = parseInt(button.dataset.requestId, 10);
            const title = button.dataset.title || 'this dispatch';
            const row = button.closest('tr');

            if (!TV.confirm('Publish "' + title + '" to the archive?')) {
                return;
            }

            button.classList.add('is-busy');

            try {
                const result = await TV.api('api/admin/approve-request', {
                    method: 'POST',
                    body: { request_id: requestId }
                });

                const status = row.querySelector('[data-request-status]');
                if (status) {
                    status.className = 'tag tag--approved';
                    status.textContent = 'Approved';
                }

                const actions = row.querySelector('.cell-actions');
                if (actions) {
                    actions.innerHTML = '<a class="btn btn--sm btn--ghost" href="'
                        + TV.escape(result.post_url) + '">View post</a>';
                }

                row.classList.add('is-leaving');
                setBadge('pending', result.counts.pending);
                TV.toast('success', result.message);
            } catch (error) {
                button.classList.remove('is-busy');
                TV.toast('error', error.message);
            }
        });

        document.addEventListener('click', async function (event) {
            const button = event.target.closest('[data-admin-delete-comment]');
            if (!button) {
                return;
            }

            const commentId = parseInt(button.dataset.commentId, 10);
            const row = button.closest('tr');

            if (!TV.confirm('Delete this comment? It cannot be brought back.')) {
                return;
            }

            button.classList.add('is-busy');

            try {
                const result = await TV.api('api/comments/' + commentId, {
                    method: 'DELETE',
                    body: {}
                });

                row.classList.add('is-leaving');
                setTimeout(function () {
                    const body = row.parentNode;
                    row.remove();
                    if (body && body.children.length === 0) {
                        showCommentsEmpty(body.closest('.panel'));
                    }
                }, 180);

                setBadge('comments', result.total);
                TV.toast('success', result.message);
            } catch (error) {
                button.classList.remove('is-busy');
                TV.toast('error', error.message);
            }
        });

        document.addEventListener('click', async function (event) {
            const button = event.target.closest('.js-approve-reset');
            if (!button) {
                return;
            }

            const requestId = parseInt(button.dataset.id, 10);
            const userEmail = button.dataset.email;
            const row = button.closest('tr');

            if (!TV.confirm('Approve this reset? The user\'s password will be changed immediately.')) {
                return;
            }

            button.classList.add('is-busy');

            try {
                const result = await TV.api('api/admin/approve-reset', {
                    method: 'POST',
                    body: { request_id: requestId }
                });

                row.classList.add('is-leaving');
                setTimeout(function () {
                    const tbody = row.parentNode;
                    row.remove();
                    if (tbody && tbody.children.length === 0) {
                        const tableWrap = tbody.closest('.table-wrap');
                        if (tableWrap) {
                            tableWrap.outerHTML = '<div class="empty-state"><span class="empty-state__icon">inbox</span><h3>No pending requests</h3><p>When a user forgets their password, their request will appear here.</p></div>';
                        }
                    }
                }, 180);

                TV.toast('success', 'Reset successful.');

                const subject = encodeURIComponent('Your TravelVista Password');
                const body = encodeURIComponent(
                    "Hello,\n\n" +
                    "Your password for TravelVista has been reset by an administrator.\n\n" +
                    "Your new temporary password is: " + result.password + "\n\n" +
                    "Please log in and change this password immediately from your Profile page.\n\n" +
                    "Best regards,\nTravelVista Admin"
                );

                const mailtoUrl = 'mailto:' + encodeURIComponent(result.email)
                                + '?subject=' + subject + '&body=' + body;

                const overlay = document.createElement('div');
                overlay.className = 'overlay overlay--active';
                overlay.innerHTML = ''
                    + '<div class="modal" role="dialog" aria-modal="true" tabindex="-1">'
                    +   '<div class="modal__header">'
                    +     '<h2 class="h3">Password Reset Successful</h2>'
                    +   '</div>'
                    +   '<div class="modal__body">'
                    +     '<p>The user\'s password has been instantly changed to:</p>'
                    +     '<div class="modal__password-box">' + TV.escape(result.password) + '</div>'
                    +     '<a href="' + TV.escape(mailtoUrl) + '" class="btn btn--primary btn--block">'
                    +       'Draft Email to User</a>'
                    +   '</div>'
                    +   '<div class="modal__footer">'
                    +     '<button type="button" class="btn btn--ghost js-close-modal">'
                    +       'I\'ve sent the email</button>'
                    +   '</div>'
                    + '</div>';

                document.body.appendChild(overlay);
                const focusable = overlay.querySelector('a');
                if (focusable) focusable.focus();

                overlay.addEventListener('click', function (e) {
                    if (e.target.closest('.js-close-modal')) {
                        overlay.remove();
                    }
                });

            } catch (error) {
                button.classList.remove('is-busy');
                TV.toast('error', error.message);
            }
        });

        // the select submits its own form; the Set button does the same without JS

        document.addEventListener('change', function (event) {
            const select = event.target.closest('[data-autosubmit]');
            if (select && select.form) {
                select.form.requestSubmit();
            }
        });

        document.addEventListener('submit', function (event) {
            const form = event.target.closest('[data-confirm]');
            if (form && !TV.confirm(form.dataset.confirm)) {
                event.preventDefault();
            }
        });

        const addToggle = document.getElementById('addUserToggle');
        const addPanel = document.getElementById('addUserPanel');

        if (addToggle && addPanel) {
            addToggle.addEventListener('click', function () {
                const open = addPanel.hidden;
                addPanel.hidden = !open;
                addToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
                addToggle.textContent = open ? 'Cancel' : 'Add a user';

                if (open) {
                    const firstField = addPanel.querySelector('input');
                    if (firstField) {
                        firstField.focus();
                    }
                }
            });
        }

        function setBadge(key, value) {
            document.querySelectorAll('[data-count="' + key + '"]').forEach(function (el) {
                el.textContent = value;
                if (el.classList.contains('sidenav__badge')) {
                    el.hidden = value === 0;
                }
            });
        }

        function showCommentsEmpty(panel) {
            if (!panel) {
                return;
            }
            panel.innerHTML = '<div class="empty"><h3>No comments left</h3>'
                + '<p>Nothing is waiting for moderation.</p></div>';
        }
    });
})();
