<?php

class User
{
    public static function findById(int $id): ?array
    {
        return Database::one('SELECT * FROM users WHERE id = ?', [$id]);
    }

    public static function findByEmail(string $email): ?array
    {
        return Database::one('SELECT * FROM users WHERE email = ?', [$email]);
    }

    public static function emailTaken(string $email, ?int $exceptId = null): bool
    {
        if ($exceptId === null) {
            return (bool) Database::value('SELECT 1 FROM users WHERE email = ?', [$email]);
        }
        return (bool) Database::value(
            'SELECT 1 FROM users WHERE email = ? AND id <> ?',
            [$email, $exceptId]
        );
    }

    public static function search(string $term = '', string $role = '', string $verified = ''): array
    {
        $sql = 'SELECT u.*,
                       (SELECT COUNT(*) FROM posts p WHERE p.scout_id = u.id) AS post_count,
                       (SELECT COUNT(*) FROM comments c WHERE c.user_id = u.id) AS comment_count
                FROM users u WHERE 1 = 1';
        $params = [];

        if ($term !== '') {
            $sql .= ' AND (u.name LIKE ? OR u.email LIKE ?)';
            $params[] = '%' . $term . '%';
            $params[] = '%' . $term . '%';
        }
        if (in_array($role, ['admin', 'scout', 'user'], true)) {
            $sql .= ' AND u.role = ?';
            $params[] = $role;
        }
        if ($verified === '0' || $verified === '1') {
            $sql .= ' AND u.is_verified = ?';
            $params[] = (int) $verified;
        }

        $sql .= ' ORDER BY u.is_verified ASC, u.created_at DESC';
        return Database::all($sql, $params);
    }

    public static function counts(): array
    {
        $rows = Database::all('SELECT role, COUNT(*) AS total FROM users GROUP BY role');
        $counts = ['admin' => 0, 'scout' => 0, 'user' => 0, 'total' => 0, 'unverified' => 0];

        foreach ($rows as $row) {
            $counts[$row['role']] = (int) $row['total'];
            $counts['total'] += (int) $row['total'];
        }
        $counts['unverified'] = (int) Database::value('SELECT COUNT(*) FROM users WHERE is_verified = 0');

        return $counts;
    }

    public static function create(string $name, string $email, string $password, string $role, int $verified = 0): int
    {
        return Database::insert(
            'INSERT INTO users (name, email, password_hash, role, is_verified)
             VALUES (?, ?, ?, ?, ?)',
            [$name, $email, password_hash($password, PASSWORD_DEFAULT), $role, $verified]
        );
    }

    public static function updateProfile(int $id, string $name, string $email): void
    {
        Database::run('UPDATE users SET name = ?, email = ? WHERE id = ?', [$name, $email, $id]);
    }

    public static function updateName(int $id, string $name): void
    {
        Database::run('UPDATE users SET name = ? WHERE id = ?', [$name, $id]);
    }

    public static function updatePicture(int $id, string $filename): void
    {
        Database::run('UPDATE users SET profile_picture = ? WHERE id = ?', [$filename, $id]);
    }

    public static function updatePassword(int $id, string $password): void
    {
        Database::run(
            'UPDATE users SET password_hash = ?, remember_token = NULL WHERE id = ?',
            [password_hash($password, PASSWORD_DEFAULT), $id]
        );
    }

    public static function setVerified(int $id, int $verified): void
    {
        Database::run('UPDATE users SET is_verified = ? WHERE id = ?', [$verified, $id]);
    }

    public static function setRole(int $id, string $role): void
    {
        Database::run('UPDATE users SET role = ? WHERE id = ?', [$role, $id]);
    }

    public static function setRememberToken(int $id, ?string $tokenHash): void
    {
        Database::run('UPDATE users SET remember_token = ? WHERE id = ?', [$tokenHash, $id]);
    }

    // ON DELETE CASCADE takes their posts, requests, wishlist rows and comments
    public static function delete(int $id): void
    {
        Database::run('DELETE FROM users WHERE id = ?', [$id]);
    }

    public static function attempt(string $email, string $password): ?array
    {
        $user = self::findByEmail($email);

        if ($user === null || !password_verify($password, $user['password_hash'])) {
            return null;
        }

        // re-hash if PHP's default cost has changed since sign-up
        if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
            self::updatePassword((int) $user['id'], $password);
        }
        return $user;
    }

    public static function passwordMatches(int $id, string $password): bool
    {
        $hash = Database::value('SELECT password_hash FROM users WHERE id = ?', [$id]);
        return is_string($hash) && password_verify($password, $hash);
    }

    public static function verifyPassword(int $userId, string $password): bool
    {
        return self::passwordMatches($userId, $password);
    }

    public static function adminCount(): int
    {
        return (int) Database::value("SELECT COUNT(*) FROM users WHERE role = 'admin'");
    }
}
