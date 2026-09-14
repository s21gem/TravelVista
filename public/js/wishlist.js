(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {

        const body = document.getElementById('wishlistBody');
        const list = document.getElementById('savedList');

        if (!body || !list) {
            return;
        }

        document.addEventListener('tv:wishlist', function (event) {
            const result = event.detail;

            if (result.saved) {
                return;
            }

            const row = list.querySelector('[data-saved-post="' + result.post_id + '"]');
            if (!row) {
                return;
            }

            row.classList.add('is-leaving');
            fadeOut(row, function () {
                if (list.children.length === 0) {
                    showEmpty();
                }
            });

            const count = document.getElementById('savedCount');
            if (count) {
                count.textContent = result.count;
            }

            const total = document.getElementById('wishlistTotal');
            if (total && result.total) {
                total.textContent = result.total;
            }
        });

        // a background tab never fires transitionend, so the timer is the fallback
        function fadeOut(element, done) {
            var finished = false;

            function finish() {
                if (finished) {
                    return;
                }
                finished = true;
                element.remove();
                done();
            }

            element.addEventListener('transitionend', finish, { once: true });
            setTimeout(finish, 400);
        }

        function showEmpty() {
            body.innerHTML = '<div class="empty"><h3>Nothing saved yet</h3>'
                + '<p>When a dispatch looks like somewhere you would actually go, save it here. '
                + 'The wishlist keeps a running total of what the trips would cost.</p>'
                + '<a class="btn btn--primary" href="' + TV.url('?page=browse') + '">'
                + 'Browse the archive</a></div>';
        }
    });
})();
