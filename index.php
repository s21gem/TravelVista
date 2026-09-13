<?php

require_once __DIR__ . '/config/init.php';
require_once APP_ROOT . '/core/Router.php';
require_once APP_ROOT . '/core/Controller.php';

$router = new Router();

// Auth
$router->add('login', 'AuthController', 'login');
$router->add('register', 'AuthController', 'register');
$router->add('logout', 'AuthController', 'logout');
$router->add('pending', 'AuthController', 'pending');
$router->add('forgot-password', 'AuthController', 'forgotPassword');
$router->add('forgot-password-submit', 'AuthController', 'submitResetRequest');

// Profile
$router->add('profile', 'ProfileController', 'index');
$router->add('profile/update', 'ProfileController', 'updateDetails');
$router->add('profile/password', 'ProfileController', 'updatePassword');

// Posts (Browse & Show)
$router->add('browse', 'PostController', 'browse');
$router->add('post', 'PostController', 'show');

// Wishlist
$router->add('wishlist', 'WishlistController', 'index');

// Scout Dashboard
$router->add('scout/dashboard', 'ScoutController', 'dashboard');
$router->add('scout/requests', 'ScoutController', 'requests');
$router->add('scout/published', 'ScoutController', 'published');
$router->add('scout/request_form', 'ScoutController', 'requestForm');
$router->add('scout/submit_request', 'ScoutController', 'submitRequest');

// Admin Dashboard
$router->add('admin/dashboard', 'AdminController', 'dashboard');
$router->add('admin/users', 'AdminController', 'users');
$router->add('admin/user_action', 'AdminController', 'userAction');
$router->add('admin/requests', 'AdminController', 'requests');
$router->add('admin/review', 'AdminController', 'review');
$router->add('admin/review_action', 'AdminController', 'reviewAction');
$router->add('admin/posts', 'AdminController', 'posts');
$router->add('admin/post_edit', 'AdminController', 'postEdit');
$router->add('admin/post_update', 'AdminController', 'postUpdate');
$router->add('admin/comments', 'AdminController', 'comments');
$router->add('admin/resets', 'AdminController', 'resets');

// API - JSON endpoints. Paths follow the PRD.
$router->add('api/wishlist/add', 'WishlistController', 'apiAdd');           // POST
$router->add('api/wishlist/remove', 'WishlistController', 'apiRemove');     // DELETE
$router->add('api/comments/add', 'CommentController', 'apiAdd');            // POST
$router->add('api/comments/{id}', 'CommentController', 'apiDelete');        // DELETE
$router->add('api/posts/search', 'PostController', 'apiSearch');            // GET
$router->add('api/posts/filter', 'PostController', 'apiFilter');            // GET

// Supporting endpoints the PRD does not name, kept in the same path style.
$router->add('api/posts/cost-estimate', 'PostController', 'apiCostEstimate');
$router->add('api/users/check-email', 'AuthController', 'apiCheckEmail');
$router->add('api/admin/verify-user', 'AdminController', 'apiVerify');
$router->add('api/admin/approve-request', 'AdminController', 'apiApprove');
$router->add('api/admin/approve-reset', 'AdminController', 'apiApproveReset');
$router->add('api/scout/requests/{id}', 'ScoutController', 'apiDelete');    // DELETE

// Home
$router->add('home', 'HomeController', 'index');

// Which route was asked for?
// A path URL wins (/TravelVista/api/wishlist/add), otherwise fall back to
// ?page=, which is still what every page link and form uses.
$page = 'home';

$path = (string) parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = urldecode($path);

// drop the project folder from the front
if (strpos($path, BASE_URL) === 0) {
    $path = substr($path, strlen(BASE_URL));
}
$path = trim($path, '/');

// also accept /TravelVista/index.php/api/... if rewriting is unavailable
if (strpos($path, 'index.php') === 0) {
    $path = trim(substr($path, strlen('index.php')), '/');
}

if ($path !== '') {
    $page = $path;
} elseif (isset($_GET['page']) && $_GET['page'] !== '') {
    $page = (string) $_GET['page'];
}

$router->dispatch($page);
