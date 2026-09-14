<?php

$avatar = avatar_url(isset($user['profile_picture']) ? $user['profile_picture'] : null);
?>

<div class="wrap shell">

    <aside class="sidenav profile-card">
        <div class="profile-card__head">
            <img src="<?= e($avatar) ?>" alt="Avatar" class="profile-card__avatar">
            <h3 class="profile-card__name"><?= e($user['name']) ?></h3>
            <span class="tag tag--<?= e($user['role']) ?>"><?= e(ucfirst($user['role'])) ?></span>
        </div>
    </aside>

    <div class="shell__main">
        <?php require APP_ROOT . '/app/Views/layouts/flash.php'; ?>

        <div class="page-head">
            <div>
                <span class="eyebrow">Settings</span>
                <h1>My Profile</h1>
            </div>
        </div>

        <div class="panel">
            <div class="panel__head">
                <h2 class="panel__title">Update Details</h2>
            </div>
            <div class="panel__body">
                <form action="<?= url('?page=profile/update') ?>" method="post" enctype="multipart/form-data" class="form stack" data-validate>
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="update_profile">

                    <div class="field">
                        <label class="field__label" for="name">Name</label>
                        <input class="input" type="text" id="name" name="name"
                               value="<?= e(isset($old['name']) ? $old['name'] : $user['name']) ?>" required
                               data-rules="required" data-min="2" data-max="100" data-label="Name">
                        <p class="field__error" data-error-for="name"><?= e(isset($errors['name']) ? $errors['name'] : '') ?></p>
                    </div>

                    <div class="field">
                        <label class="field__label" for="email">Email</label>
                        <input class="input" type="email" id="email" name="email"
                               value="<?= e(isset($old['email']) ? $old['email'] : $user['email']) ?>" required
                               data-rules="required email" data-label="Email">
                        <p class="field__error" data-error-for="email"><?= e(isset($errors['email']) ? $errors['email'] : '') ?></p>
                    </div>

                    <div class="field">
                        <label class="field__label" for="profile_picture">Profile Picture</label>
                        <input class="input" type="file" id="profile_picture" name="profile_picture"
                               accept="image/jpeg,image/png,image/webp"
                               data-rules="image" data-max-bytes="<?= (int) MAX_UPLOAD_BYTES ?>" data-label="Profile Picture">
                        <p class="field__error" data-error-for="profile_picture"><?= e(isset($errors['profile_picture']) ? $errors['profile_picture'] : '') ?></p>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn--primary">Save Profile</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="panel mt-6">
            <div class="panel__head">
                <h2 class="panel__title">Change Password</h2>
            </div>
            <div class="panel__body">
                <form action="<?= url('?page=profile/password') ?>" method="post" class="form stack" data-validate>
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="change_password">

                    <div class="field">
                        <label class="field__label" for="current_password">Current Password</label>
                        <input class="input" type="password" id="current_password" name="current_password" required
                               autocomplete="current-password"
                               data-rules="required" data-label="Current Password">
                        <p class="field__error" data-error-for="current_password"><?= e(isset($errors['current_password']) ? $errors['current_password'] : '') ?></p>
                    </div>

                    <div class="field">
                        <label class="field__label" for="new_password">New Password</label>
                        <input class="input" type="password" id="new_password" name="new_password" required
                               autocomplete="new-password"
                               data-rules="required" data-min="8" data-label="New Password">
                        <p class="field__hint">Make it at least 8 characters.</p>
                        <p class="field__error" data-error-for="new_password"><?= e(isset($errors['new_password']) ? $errors['new_password'] : '') ?></p>
                    </div>

                    <div class="field">
                        <label class="field__label" for="new_password_confirm">Confirm New Password</label>
                        <input class="input" type="password" id="new_password_confirm" name="new_password_confirm" required
                               autocomplete="new-password"
                               data-rules="required" data-match="new_password" data-label="Confirm New Password"
                               data-match-message="The passwords do not match.">
                        <p class="field__error" data-error-for="new_password_confirm"><?= e(isset($errors['new_password_confirm']) ? $errors['new_password_confirm'] : '') ?></p>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn--warning">Update Password</button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>
