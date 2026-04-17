<?php

declare(strict_types=1);

final class Auth
{
    public static function user(): ?array
    {
        return $_SESSION['auth_user'] ?? null;
    }

    public static function check(): bool
    {
        return isset($_SESSION['auth_user']);
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            redirect('/login.php');
        }
    }

    public static function roleCode(): ?string
    {
        return self::user()['role_code'] ?? null;
    }

    public static function hasRole(string|array $roles): bool
    {
        $currentRole = self::roleCode();

        if ($currentRole === null) {
            return false;
        }

        $roles = is_array($roles) ? $roles : [$roles];

        return in_array($currentRole, $roles, true);
    }

    public static function requireRole(string|array $roles): void
    {
        self::requireLogin();

        if (!self::hasRole($roles)) {
            flash_set('error', 'คุณไม่มีสิทธิ์เข้าใช้งานส่วนนี้');
            redirect('/dashboard.php');
        }
    }

    public static function login(array $user): void
    {
        $_SESSION['auth_user'] = $user;
    }

    public static function logout(): void
    {
        unset($_SESSION['auth_user']);
    }

    public static function canAccessPublicReport(): bool
    {
        return !empty($_SESSION['public_report_access']);
    }

    public static function grantPublicReportAccess(): void
    {
        $_SESSION['public_report_access'] = true;
    }

    public static function revokePublicReportAccess(): void
    {
        unset($_SESSION['public_report_access']);
    }
}
