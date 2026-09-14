<?php

class Wishlist
{
    public static function forUser(int $userId): array
    {
        return Database::all(
            'SELECT w.id AS wishlist_id, w.added_at,
                    p.id, p.title, p.country, p.genre, p.cost_level,
                    p.travel_medium_info, p.short_history, p.image,
                    c.base_cost, c.currency
             FROM wishlist w
             JOIN posts p ON p.id = w.post_id
             LEFT JOIN cost_estimates c ON c.post_id = p.id
             WHERE w.user_id = ? AND p.status = ?
             ORDER BY w.added_at DESC',
            [$userId, 'approved']
        );
    }

    public static function postIds(int $userId): array
    {
        $rows = Database::all('SELECT post_id FROM wishlist WHERE user_id = ?', [$userId]);
        return array_map('intval', array_column($rows, 'post_id'));
    }

    public static function has(int $userId, int $postId): bool
    {
        return (bool) Database::value(
            'SELECT 1 FROM wishlist WHERE user_id = ? AND post_id = ?',
            [$userId, $postId]
        );
    }

    public static function count(int $userId): int
    {
        return (int) Database::value('SELECT COUNT(*) FROM wishlist WHERE user_id = ?', [$userId]);
    }

    // the unique key on (user_id, post_id) makes a repeat save a no-op
    public static function add(int $userId, int $postId): void
    {
        Database::run(
            'INSERT IGNORE INTO wishlist (user_id, post_id) VALUES (?, ?)',
            [$userId, $postId]
        );
    }

    public static function remove(int $userId, int $postId): bool
    {
        return Database::run(
            'DELETE FROM wishlist WHERE user_id = ? AND post_id = ?',
            [$userId, $postId]
        )->rowCount() > 0;
    }

    public static function estimatedTotal(int $userId): float
    {
        // the fallback levels are bound from COST_BASE so the figures here can
        // never drift from what the rest of the site quotes
        $value = Database::value(
            "SELECT COALESCE(SUM(COALESCE(c.base_cost,
                        CASE p.cost_level WHEN 'low' THEN ? WHEN 'high' THEN ? ELSE ? END)), 0)
             FROM wishlist w
             JOIN posts p ON p.id = w.post_id
             LEFT JOIN cost_estimates c ON c.post_id = p.id
             WHERE w.user_id = ?",
            [cost_base('low'), cost_base('high'), cost_base('medium'), $userId]
        );
        return (float) $value;
    }
}
