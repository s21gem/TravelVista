# TravelVista

A travel guide website built for **Web Technologies — Project 01**.

Scouts write up destinations they have visited, admins review and publish them,
and travellers search the archive, keep a wishlist, estimate trip costs and
leave notes. Non-registered visitors get a public home page only.

Plain **PHP 8 · MySQL/MariaDB · PDO · vanilla JavaScript · one global
stylesheet**, laid out as **MVC**. No frameworks, no Composer, no npm, no build
step.

---

## Requirements

| | |
|---|---|
| PHP | 8.1 or newer, with `pdo_mysql`, `fileinfo` and `mbstring` |
| Database | MySQL 8 or MariaDB 10.4+ |
| Server | Apache (XAMPP/WAMP/Laragon) or PHP's built-in server |

Nothing else. There are no dependencies to install.

## Setup

**1. Put the project where your server can see it** — `xampp/htdocs/TravelVista`.
The folder name matters: `BASE_URL` at the bottom of `config/config.php` has to
match it, and it ships set to `/TravelVista`. Rename one and you must rename the
other.

**2. Point it at your database.** Open [`config/config.php`](config/config.php)
and edit the top block if your credentials differ from the XAMPP defaults:

```php
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'travelvista');
define('DB_USER', 'root');
define('DB_PASS', '');
```

**3. Create the schema.** Import `database/schema.sql` through phpMyAdmin, or:

```bash
mysql -u root -p < database/schema.sql
```

**4. Load the demo data** (recommended — it gives you accounts in every role,
thirty-nine published destinations, a review queue and some comments):

```bash
mysql -u root -p travelvista < database/seed.sql
```

**5. Open it.** Start Apache and MySQL in the XAMPP control panel, then visit
`http://localhost/TravelVista/`. If your Apache listens on another port, put it
in the URL — `http://localhost:8080/TravelVista/`.

### Demo accounts

Every seeded account uses the password `password`.

| Email | Role | Status |
|---|---|---|
| `admin@travelvista.test` | admin | verified |
| `rina@travelvista.test` | scout | verified |
| `tomas@travelvista.test` | scout | verified |
| `priya@travelvista.test` | scout | **pending** |
| `jonah@travelvista.test` | user | verified |
| `leila@travelvista.test` | user | verified |
| `sam@travelvista.test` | user | **pending** |

The pending accounts are there on purpose, so you can watch the admin verify
someone and see the site unlock for them.

---

## How the MVC pieces fit together

```
index.php               the single front controller: registers every route,
                        works through ?page= or through a path URL

config/                 configuration and the request bootstrap
  config.php              database credentials, paths, domain vocabulary
  init.php                required first by EVERY request: session, models, auth

core/                   the shared infrastructure
  Router.php              matches a route, loads the controller, calls the action
  Controller.php          render(): header + view + footer, plus the nav counts
  Database.php            the PDO connection and the run/all/one/value helpers
  Auth.php                sessions, "remember me", the role gates
  Helpers.php             escaping, URLs, flash messages, CSRF, JSON, formatting
  Upload.php              image validation and storage

app/Models/             data — one class per table, all PDO, all prepared
  User.php                Post.php              PostRequest.php
  Wishlist.php            Comment.php           CostEstimate.php
  ResetRequest.php

app/Controllers/        request handlers — forms post here, then redirect,
                        and the api* actions answer application/json instead
  AuthController.php      ProfileController.php
  HomeController.php      PostController.php
  ScoutController.php     AdminController.php
  WishlistController.php  CommentController.php

app/Views/              presentation only — no SQL, no business logic
  layouts/                header, footer, flash, dispatch card, the two sidenavs
  auth/                   login, register, pending, forgot_password
  home/ profile/ wishlist/
  posts/                  browse, show, not_found
  scout/                  dashboard, requests, request_form, published
  admin/                  dashboard, users, requests, review,
                          posts, post_edit, comments, resets

public/                 everything the browser fetches directly
  css/global.css          the single global stylesheet for the whole site
  js/                     app.js validation.js browse.js comments.js
                          cost.js scout.js wishlist.js admin.js
  images/genre/           the flat SVG cover used when a post has no upload
  uploads/                avatars/ and posts/ — written at runtime

database/               schema.sql and seed.sql
```

The flow is always the same:

```
browser  ->  index.php            matches the route
         ->  app/Controllers/     validates, authorises, calls a model
         ->  app/Models/          the only place that touches the database
         ->  app/Views/           prints HTML
                                  (or the controller returns JSON instead)
```

No view ever writes to the database, and no model ever prints HTML.

---

## Where each PRD task lives

Routes are written the way the links do it, `?page=…`. The AJAX paths are the
ones the PRD names; `index.php` answers them either as `index.php/api/…` or as
a clean `/api/…` when Apache is rewriting.

### Task 1 — Auth, registration, profile, home, wishlist

| Requirement | Where |
|---|---|
| Registration for all three roles, `is_verified = 0` | `app/Views/auth/register.php` → `AuthController::register()` |
| Password ≥ 8 chars, confirmed, unique email, hashed | `AuthController::register()`, `User::create()` |
| Login creating `$_SESSION['user_id'｜'user_name'｜'user_role']` | `AuthController::login()`, `auth_login()` |
| "Remember me" — hashed token + 30-day cookie | `auth_remember()` / `auth_restore_from_cookie()` |
| Profile: name, email, picture, password change | `app/Views/profile/index.php` → `ProfileController` |
| Verification notice for unapproved accounts | `app/Views/auth/pending.php`, `require_verified()` |
| Logout destroying session and cookie | `AuthController::logout()`, `auth_logout()` |
| Role-aware navbar | `app/Views/layouts/header.php` |
| Home page — three states | `app/Views/home/index.php`, `HomeController::index()` |
| Wishlist add / view / remove | `api/wishlist/add`, `?page=wishlist`, `api/wishlist/remove` → `WishlistController` |

### Task 2 — Scout post requests

| Requirement | Where |
|---|---|
| Scout gate (`role='scout'` and verified) | `require_role('scout')` |
| Create a post request into `post_requests` | `app/Views/scout/request_form.php` → `ScoutController::submitRequest()` |
| My requests, with edit/delete only while pending | `app/Views/scout/requests.php`, `PostRequest::editableBy()` |
| Edit a pending request | same form, `?page=scout/request_form&id=` |
| Delete over AJAX with confirmation | `api/scout/requests/{id}` → `ScoutController::apiDelete()` |
| View approved posts (read-only) | `app/Views/scout/published.php` |
| Request changes to a published post | same form, `?change=` → `original_post_id` |
| Image upload to `public/uploads/posts/` | `core/Upload.php` |

### Task 3 — Admin dashboard

| Requirement | Where |
|---|---|
| Admin gate | `require_role('admin')` |
| Dashboard counts | `app/Views/admin/dashboard.php`, `AdminController::dashboard()` |
| Add / verify / change role / delete users | `app/Views/admin/users.php` → `AdminController::userAction()` |
| Verify toggle over AJAX | `api/admin/verify-user` → `AdminController::apiVerify()` |
| Moderation queue and full review | `app/Views/admin/requests.php`, `app/Views/admin/review.php` |
| Approve → publish the request into `posts` | `PostRequest::publish()`, `api/admin/approve-request` |
| Reject with a reason | `AdminController::reviewAction()`, `PostRequest::setStatus()` |
| Edit / hide / delete any post | `app/Views/admin/post_edit.php`, `AdminController::postUpdate()` |
| Delete any comment | `app/Views/admin/comments.php` → `api/comments/{id}` |

### Task 4 — Browse, search, comments, cost

| Requirement | Where |
|---|---|
| Browse published posts as cards | `app/Views/posts/browse.php`, `PostController::browse()` |
| Post detail with full record and images | `app/Views/posts/show.php`, `PostController::show()` |
| Live search on keystroke | `api/posts/search` + `public/js/browse.js` |
| Country / genre / cost filters over AJAX | `api/posts/filter` + `public/js/browse.js` |
| View, post and delete comments | `app/Views/posts/show.php`, `api/comments/add`, `api/comments/{id}` |
| Probable cost + calculator | `api/posts/cost-estimate`, `public/js/cost.js`, `CostEstimate::calculate()` |

---

## The cost estimate

Every post carries a base cost in `cost_estimates`. When a scout does not give
one, the PRD's mapping supplies it: **low = $500, medium = $1,500, high = $3,000**
for one traveller for one week.

The trip total is worked out the same way in PHP and in JavaScript, so the
figure on screen never disagrees with the one the server returns:

```
weeks = days / 7
party = 1 + (travellers - 1) × 0.85     the first traveller pays full
total = base × weeks × party
```

The browser updates the number instantly as the steppers move, then confirms it
against `api/posts/cost-estimate`. If that request fails the calculator keeps
working and says so.

---

## Security

| Concern | How it is handled |
|---|---|
| SQL injection | Every query is a prepared statement through `Database::run()`. No SQL is built by concatenating input. Values that cannot be bound (sort order, genre and cost lists) are matched against a fixed whitelist first. |
| XSS | Everything printed goes through `e()` (`htmlspecialchars` with `ENT_QUOTES`). Comment text is stored raw and escaped on output. `TV.escape()` does the same for anything JavaScript writes. |
| CSRF | A per-session token in every form (`csrf_field()`), checked by `csrf_guard()`. AJAX sends it in the body and the `X-CSRF-Token` header; `api_csrf_guard()` checks it. |
| Passwords | `password_hash()` on the way in, `password_verify()` on the way out, re-hashed if PHP's cost changes. Never logged, never echoed. |
| "Remember me" | The cookie holds `id:secret`; only `sha256(secret)` is stored, and the secret is rotated on every use. A leaked database row cannot be replayed. |
| Sessions | `session_regenerate_id(true)` on login, HttpOnly + SameSite=Lax cookies, `session_start()` before any auth check. |
| Authorisation | `require_login()`, `require_verified()` and `require_role()` on pages; `api_require_role()` on endpoints. Ownership is re-checked on every write — a scout can only edit their own pending requests, a traveller can only delete their own comment. |
| Uploads | MIME type read from the file's own bytes with `finfo`, not from the browser; size capped; the stored filename is generated, never taken from the upload; `uploads/.htaccess` turns off PHP execution. |
| Direct access | `config/`, `model/` and `database/` carry an `.htaccess` that denies HTTP requests. |

---

## Validation

Every form is validated **twice**, and the server side is the one that counts.

* **Client** — `public/js/validation.js`. Fields declare what they need in HTML
  (`data-rules="required email"`, `data-min="8"`, `data-match="password"`), so
  adding a rule needs no new JavaScript. It also drives the password strength
  bar, the character counters, the image preview and the live "is this email
  taken?" check.
* **Server** — the matching controller, before any write. `Post::validate()`
  holds the destination rules shared by the scout form and the admin editor.
  Errors and the submitted values survive the redirect, so the form comes back
  filled in with the messages inline.

---

## Design

The site is built around one idea: a destination here is not a listing, it is a
**dispatch filed by a scout and catalogued**. That shows up in three places.

* **The ledger.** Wherever a destination appears — a browse card, a wishlist
  row, a moderation table, the detail page — it carries the same ruled
  monospace block of country, medium and cost. It is the thing that makes the
  public site and the back office read as one product.
* **The cost meter.** `low / medium / high` is drawn as a three-segment brass
  meter rather than written as a word, turning an enum into an instrument
  reading you can compare at a glance across a grid.
* **File numbers.** Records are referred to as `TV-0042` and `REQ-0007` in
  monospace, which is how a scout and an admin actually refer to them across
  the review screens.

Deep petrol green for the chrome, cool chart-paper grey for the working
surface, brass for anything actionable. Type is Bricolage Grotesque for
display, Instrument Sans for text and IBM Plex Mono for data. Cover art is flat
geometric SVG, one per genre, used whenever a post has no uploaded image.

All styling is in the single global stylesheet `public/css/global.css`, and all
behaviour is in `js/`. There is no inline `style` or `on*` attribute doing
layout or logic work.

---

## Notes on the schema

The PRD's tables are used as given. Three columns were added, and nothing was
dropped or renamed:

* `users.remember_token` — required by the PRD's own "Remember Me" requirement.
* `posts.country_representation` and `posts.image` — the PRD asks the detail
  page to show country representation and images, which need somewhere to live.
* `post_requests.original_post_id` — added exactly as the PRD suggests, so a
  change request can point at the post it would amend. `post_requests.admin_note`
  carries the rejection reason.

Foreign keys are `ON DELETE CASCADE`, so deleting a user takes their posts,
requests, wishlist rows and comments with them, and deleting a post takes its
comments, wishlist rows and cost estimate — which is what the PRD asks for.

---

## Troubleshooting

**"Database unavailable"** — MySQL is not running, or the credentials in
`config/config.php` are wrong. The page tells you which host and database it
tried.

**Images do not upload** — check that `uploads/avatars/` and `uploads/posts/`
are writable by the web server.

**Styles or scripts 404** — `BASE_URL` in `config/config.php` does not match the
folder the project sits in under `htdocs`. The folder is `TravelVista`, so the
line reads `define('BASE_URL', '/TravelVista');`. Rename the folder and you must
change that line to match.

**Everything is unverified** — that is the intended first-run state. Sign in as
`admin@travelvista.test` and verify the accounts from **Admin → Users**.
