<?php

class CostEstimate
{
    public static function forPost(int $postId): ?array
    {
        return Database::one('SELECT * FROM cost_estimates WHERE post_id = ?', [$postId]);
    }

    public static function baseCost(int $postId, string $costLevel): float
    {
        $stored = Database::value('SELECT base_cost FROM cost_estimates WHERE post_id = ?', [$postId]);
        if ($stored !== null && (float) $stored > 0) {
            return (float) $stored;
        }
        return (float) cost_base($costLevel);
    }

    public static function currency(int $postId): string
    {
        $currency = Database::value('SELECT currency FROM cost_estimates WHERE post_id = ?', [$postId]);
        return is_string($currency) && $currency !== '' ? $currency : 'USD';
    }

    public static function save(int $postId, float $baseCost, string $currency = 'USD'): void
    {
        Database::run(
            'INSERT INTO cost_estimates (post_id, base_cost, currency)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE base_cost = VALUES(base_cost), currency = VALUES(currency)',
            [$postId, $baseCost, $currency]
        );
    }

    public static function delete(int $postId): void
    {
        Database::run('DELETE FROM cost_estimates WHERE post_id = ?', [$postId]);
    }

    // same formula as cost.js
    public static function calculate(float $baseCost, int $travellers, int $days): array
    {
        $travellers = max(1, min(10, $travellers));
        $days       = max(1, min(60, $days));

        // total = base_cost x travellers x days/7, straight from the PRD.
        // The base cost is what one traveller spends in a week.
        $weeks     = $days / 7;
        $total     = $baseCost * $travellers * $weeks;
        $perPerson = $total / $travellers;

        return [
            'base_cost'   => round($baseCost, 2),
            'travellers'  => $travellers,
            'days'        => $days,
            'weeks'       => round($weeks, 2),
            'total'       => round($total, 2),
            'per_person'  => round($perPerson, 2),
            'per_day'     => round($total / $days, 2),
        ];
    }
}
