<?php

class Controller
{
    // values pulled out of a {placeholder} route, e.g. api/comments/{id}
    protected $params = [];

    public function setParams(array $params)
    {
        $this->params = $params;
    }

    protected function param(string $name): string
    {
        return isset($this->params[$name]) ? (string) $this->params[$name] : '';
    }

    protected function render(string $view, array $data = [])
    {
        extract($data);

        $user = current_user();
        $verified = $user ? (bool)$user['is_verified'] : false;
        $role = user_role();
        $savedCount = $user && $role === 'user' ? Wishlist::count((int)$user['id']) : 0;

        // Counts for the shared chrome (header badge and the two sidenavs).
        // Gathered here so the layout files stay presentation only.
        $navUserCounts = [];
        $navRequestCounts = [];
        $navPendingResets = 0;
        $navScoutCounts = [];
        $navPublishedCount = 0;

        if ($role === 'admin') {
            $navUserCounts    = User::counts();
            $navRequestCounts = PostRequest::counts();
            $navPendingResets = ResetRequest::countPending();
        } elseif ($role === 'scout' && $user) {
            $navScoutCounts    = PostRequest::countsForScout((int)$user['id']);
            $navPublishedCount = Post::countByScout((int)$user['id']);
        }

        require APP_ROOT . '/app/Views/layouts/header.php';
        require APP_ROOT . '/app/Views/' . $view . '.php';
        require APP_ROOT . '/app/Views/layouts/footer.php';
    }
}
