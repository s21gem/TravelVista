<?php

class AuthController extends Controller
{
    public function login()
    {
        if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] !== 'POST') {
            if (is_logged_in()) {
                redirect(home_for_role(isset($_SESSION['user_role']) ? $_SESSION['user_role'] : 'user'));
            }

            $errors = take_errors();
            $old    = take_old();

            // registration redirects here with ?email=
            $email = isset($old['email']) ? $old['email'] : (isset($_GET['email']) ? (string) $_GET['email'] : '');

            $this->render('auth/login', [
                'pageTitle' => 'Sign in',
                'pageScripts' => ['validation.js'],
                'errors' => $errors,
                'email' => $email
            ]);
            return;
        }

        csrf_guard();

        $email    = strtolower(field($_POST, 'email'));
        $password = isset($_POST['password']) ? (string) $_POST['password'] : '';
        $remember = isset($_POST['remember']);

        $input = ['email' => $email, 'remember' => $remember ? '1' : ''];

        $errors = [];

        if ($email === '') {
            $errors['email'] = 'Enter your email address.';
        } elseif (!valid_email($email)) {
            $errors['email'] = 'That does not look like an email address.';
        }

        if ($password === '') {
            $errors['password'] = 'Enter your password.';
        }

        if ($errors) {
            back_with_errors($errors, $input, '?page=login');
        }

        $user = User::attempt($email, $password);

        if ($user === null) {
            back_with_errors(
                ['password' => 'That email and password do not match an account.'],
                $input,
                '?page=login'
            );
        }

        auth_login($user);

        if ($remember) {
            auth_remember((int) $user['id']);
        }

        if (!$user['is_verified']) {
            redirect('?page=pending');
        }

        flash('success', 'Signed in. Welcome back, ' . strtok($user['name'], ' ') . '.');

        $intended = isset($_SESSION['intended']) ? $_SESSION['intended'] : null;
        unset($_SESSION['intended']);

        if (is_string($intended) && str_starts_with($intended, '/') && !str_contains($intended, '//')) {
            header('Location: ' . $intended);
            exit;
        }

        redirect(home_for_role($user['role']));
    }

    public function register()
    {
        if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] !== 'POST') {
            if (is_logged_in()) {
                redirect(home_for_role(isset($_SESSION['user_role']) ? $_SESSION['user_role'] : 'user'));
            }

            $errors = take_errors();
            $old    = take_old();

            $this->render('auth/register', [
                'pageTitle' => 'Create an account',
                'pageScripts' => ['validation.js'],
                'errors' => $errors,
                'old' => $old
            ]);
            return;
        }

        csrf_guard();

        $name     = field($_POST, 'name');
        $email    = strtolower(field($_POST, 'email'));
        $password = isset($_POST['password']) ? (string) $_POST['password'] : '';
        $confirm  = isset($_POST['password_confirm']) ? (string) $_POST['password_confirm'] : '';
        $role     = field($_POST, 'role');

        $input  = ['name' => $name, 'email' => $email, 'role' => $role];
        $errors = [];

        if ($name === '') {
            $errors['name'] = 'Enter your name.';
        } elseif (mb_strlen($name) > 100) {
            $errors['name'] = 'That name is too long.';
        }

        if ($email === '') {
            $errors['email'] = 'Enter an email address.';
        } elseif (!valid_email($email)) {
            $errors['email'] = 'That does not look like an email address.';
        } elseif (User::emailTaken($email)) {
            $errors['email'] = 'An account already uses this address.';
        }

        if ($password === '') {
            $errors['password'] = 'Enter a password.';
        } elseif (strlen($password) < 8) {
            $errors['password'] = 'Use at least 8 characters.';
        }

        if ($password !== $confirm) {
            $errors['password_confirm'] = 'The passwords do not match.';
        }

        // All three roles may be requested. Nothing is granted on sign-up: the
        // account is created unverified, so an existing admin still has to
        // approve it before the role does anything.
        if (!in_array($role, ['admin', 'scout', 'user'], true)) {
            $errors['role'] = 'Choose an account type.';
        }

        if ($errors) {
            back_with_errors($errors, $input, '?page=register');
        }

        User::create($name, $email, $password, $role, 0);

        flash('success', 'Your account was created! An admin will review it shortly.');
        redirect('?page=login&email=' . urlencode($email));
    }

    public function logout()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            csrf_guard();
            auth_logout();
            redirect('?page=login');
        }
        redirect('?page=home');
    }

    public function pending()
    {
        require_login();

        $this->render('auth/pending', [
            'pageTitle' => 'Pending Verification'
        ]);
    }

    public function forgotPassword()
    {
        if (is_logged_in()) {
            redirect(home_for_role(isset($_SESSION['user_role']) ? $_SESSION['user_role'] : 'user'));
        }

        $this->render('auth/forgot_password', [
            'pageTitle' => 'Forgot Password',
            'errors'    => take_errors(),
            'old'       => take_old()
        ]);
    }

    public function submitResetRequest()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('?page=forgot-password');
        }

        csrf_guard();

        $email = strtolower(field($_POST, 'email'));
        if ($email === '') {
            back_with_errors(['email' => 'Please enter your email address.'], [], '?page=forgot-password');
        }

        $user = User::findByEmail($email);
        if ($user) {
            // don't stack duplicate pending requests
            if (!ResetRequest::hasPending((int) $user['id'])) {
                ResetRequest::create((int) $user['id']);
            }
        }

        // same message whether or not the account exists, to prevent email enumeration
        flash('success', 'If an account exists for that email, a request has been sent to the admin.');
        redirect('?page=login');
    }

    // Tells the registration and add-user forms whether an address is still free,
    // so the visitor finds out while typing instead of after a failed submit.
    //
    // Registration already gives this answer away - addresses are unique, so any
    // signup form must reject one that is taken. The throttle below is what stops
    // that from turning into a cheap way to test a long list of addresses.
    public function apiCheckEmail()
    {
        api_require_method('POST');

        $input = json_input();
        api_csrf_guard($input);

        if (!$this->emailCheckAllowed()) {
            json_error('Too many checks in a row. Wait a moment.', 429);
        }

        $email = isset($input['email']) ? strtolower(trim((string) $input['email'])) : '';

        // nothing worth looking up yet - the form's own format rule covers this
        if ($email === '' || !valid_email($email)) {
            json_out(['ok' => true, 'checked' => false, 'available' => true]);
        }

        // someone signed in who keeps their own address has not taken it from anyone
        $me = current_user();
        $exceptId = $me ? (int) $me['id'] : null;

        json_out([
            'ok'        => true,
            'checked'   => true,
            'available' => !User::emailTaken($email, $exceptId)
        ]);
    }

    // 20 checks a minute is far more than typing an address needs
    private function emailCheckAllowed(): bool
    {
        $now = time();

        if (!isset($_SESSION['email_checks']['start'])
            || $_SESSION['email_checks']['start'] < $now - 60) {
            $_SESSION['email_checks'] = ['start' => $now, 'count' => 0];
        }

        $_SESSION['email_checks']['count']++;

        return $_SESSION['email_checks']['count'] <= 20;
    }
}
