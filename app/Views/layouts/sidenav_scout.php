<?php // $navScoutCounts and $navPublishedCount come from Controller::render() ?>
<aside class="sidenav">
    <div class="sidenav__head">
        <div class="sidenav__role">Scout desk</div>
        <div class="sidenav__name"><?= e($user['name']) ?></div>
    </div>

    <nav class="sidenav__list" aria-label="Scout">
        <a class="sidenav__link <?= nav_active('?page=scout/dashboard') ? 'is-active' : '' ?>"
           href="<?= e(url('?page=scout/dashboard')) ?>">Overview</a>

        <a class="sidenav__link <?= nav_active('?page=scout/request_form') ? 'is-active' : '' ?>"
           href="<?= e(url('?page=scout/request_form')) ?>">File a dispatch</a>

        <a class="sidenav__link <?= nav_active('?page=scout/requests') ? 'is-active' : '' ?>"
           href="<?= e(url('?page=scout/requests')) ?>">
            My requests
            <?php if ($navScoutCounts['pending'] > 0): ?>
                <span class="sidenav__badge"><?= (int) $navScoutCounts['pending'] ?></span>
            <?php endif; ?>
        </a>

        <a class="sidenav__link <?= nav_active('?page=scout/published') ? 'is-active' : '' ?>"
           href="<?= e(url('?page=scout/published')) ?>">
            Published
            <?php if ($navPublishedCount > 0): ?>
                <span class="sidenav__badge"><?= (int) $navPublishedCount ?></span>
            <?php endif; ?>
        </a>

        <a class="sidenav__link" href="<?= e(url('?page=browse')) ?>">Browse the archive</a>
    </nav>
</aside>
