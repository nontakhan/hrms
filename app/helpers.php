<?php

declare(strict_types=1);

function base_url(string $path = ''): string
{
    $baseUrl = rtrim((string) app_config('base_url', ''), '/');
    $path = ltrim($path, '/');

    return $path === '' ? $baseUrl : $baseUrl . '/' . $path;
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): never
{
    header('Location: ' . base_url($path));
    exit;
}

function flash_set(string $key, string $message): void
{
    $_SESSION['flash'][$key] = $message;
}

function flash_get(string $key): ?string
{
    $message = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);

    return $message;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(?string $token): bool
{
    return is_string($token) && hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

function old(string $key, mixed $default = ''): mixed
{
    return $_SESSION['old_input'][$key] ?? $default;
}

function old_set(array $input): void
{
    $_SESSION['old_input'] = $input;
}

function old_clear(): void
{
    unset($_SESSION['old_input']);
}

function setting(string $key, mixed $default = null): mixed
{
    try {
        $stmt = Database::connection()->prepare('SELECT setting_value FROM system_settings WHERE setting_key = :setting_key LIMIT 1');
        $stmt->execute(['setting_key' => $key]);
        $value = $stmt->fetchColumn();

        return $value !== false ? $value : $default;
    } catch (Throwable) {
        return $default;
    }
}

function upsert_setting(string $key, string $value, ?int $updatedBy = null, ?string $description = null): void
{
    $sql = <<<SQL
        INSERT INTO system_settings (setting_key, setting_value, description, updated_by, updated_at)
        VALUES (:setting_key, :setting_value, :description, :updated_by, NOW())
        ON DUPLICATE KEY UPDATE
            setting_value = VALUES(setting_value),
            description = COALESCE(VALUES(description), description),
            updated_by = VALUES(updated_by),
            updated_at = NOW()
    SQL;

    $stmt = Database::connection()->prepare($sql);
    $stmt->execute([
        'setting_key' => $key,
        'setting_value' => $value,
        'description' => $description,
        'updated_by' => $updatedBy,
    ]);
}

function fetch_all_departments(): array
{
    try {
        $stmt = Database::connection()->query(
            'SELECT id, department_code, department_name, department_type, parent_department_id, is_nursing_group
             FROM departments
             WHERE is_active = 1
             ORDER BY department_name ASC'
        );

        return $stmt->fetchAll();
    } catch (Throwable) {
        return [];
    }
}

function fetch_all_teams(): array
{
    try {
        $stmt = Database::connection()->query(
            'SELECT id, team_code, team_name, description
             FROM teams
             WHERE is_active = 1
             ORDER BY team_code ASC, team_name ASC'
        );

        return $stmt->fetchAll();
    } catch (Throwable) {
        return [];
    }
}

function fetch_roles(): array
{
    try {
        $stmt = Database::connection()->query(
            'SELECT id, role_code, role_name
             FROM roles
             ORDER BY id ASC'
        );

        return $stmt->fetchAll();
    } catch (Throwable) {
        return [];
    }
}

function fetch_incident_types(): array
{
    try {
        $stmt = Database::connection()->query(
            'SELECT id, type_code, type_name
             FROM incident_types
             WHERE is_active = 1
             ORDER BY id ASC'
        );

        return $stmt->fetchAll();
    } catch (Throwable) {
        return [];
    }
}

function fetch_severity_levels_by_type_code(?string $typeCode = null): array
{
    try {
        $sql = 'SELECT sl.id, sl.level_code, sl.level_name, it.type_code
                FROM severity_levels sl
                INNER JOIN incident_types it ON it.id = sl.incident_type_id
                WHERE sl.is_active = 1';

        $params = [];

        if ($typeCode !== null && $typeCode !== '') {
            $sql .= ' AND it.type_code = :type_code';
            $params['type_code'] = $typeCode;
        }

        $sql .= ' ORDER BY it.id ASC, sl.sort_order ASC';

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    } catch (Throwable) {
        return [];
    }
}

function active_fiscal_year(): ?array
{
    try {
        $activeId = (int) setting('active_fiscal_year_id', 0);

        if ($activeId > 0) {
            $stmt = Database::connection()->prepare(
                'SELECT id, year_label, year_short, date_start, date_end
                 FROM fiscal_years
                 WHERE id = :id
                 LIMIT 1'
            );
            $stmt->execute(['id' => $activeId]);
            $year = $stmt->fetch();
            if ($year) {
                return $year;
            }
        }

        $stmt = Database::connection()->query(
            'SELECT id, year_label, year_short, date_start, date_end
             FROM fiscal_years
             WHERE is_active = 1
             ORDER BY id DESC
             LIMIT 1'
        );

        $year = $stmt->fetch();

        return $year ?: null;
    } catch (Throwable) {
        return null;
    }
}

function fetch_fiscal_years(): array
{
    try {
        $stmt = Database::connection()->query(
            'SELECT id, year_label, year_short, date_start, date_end, is_active
             FROM fiscal_years
             ORDER BY date_start DESC, id DESC'
        );

        return $stmt->fetchAll();
    } catch (Throwable) {
        return [];
    }
}

function resolve_report_filter_range(?string $dateFrom, ?string $dateTo, int $fiscalYearId = 0): array
{
    $resolvedFrom = null;
    $resolvedTo = null;

    if ($dateFrom !== null && $dateFrom !== '') {
        $candidate = DateTimeImmutable::createFromFormat('Y-m-d', $dateFrom);
        if ($candidate !== false) {
            $resolvedFrom = $candidate->format('Y-m-d');
        }
    }

    if ($dateTo !== null && $dateTo !== '') {
        $candidate = DateTimeImmutable::createFromFormat('Y-m-d', $dateTo);
        if ($candidate !== false) {
            $resolvedTo = $candidate->format('Y-m-d');
        }
    }

    if (($resolvedFrom === null || $resolvedTo === null) && $fiscalYearId > 0) {
        try {
            $stmt = Database::connection()->prepare(
                'SELECT date_start, date_end
                 FROM fiscal_years
                 WHERE id = :id
                 LIMIT 1'
            );
            $stmt->execute(['id' => $fiscalYearId]);
            $year = $stmt->fetch();

            if ($year) {
                $resolvedFrom ??= (string) $year['date_start'];
                $resolvedTo ??= (string) $year['date_end'];
            }
        } catch (Throwable) {
            // keep current values
        }
    }

    if ($resolvedFrom !== null && $resolvedTo !== null && $resolvedFrom > $resolvedTo) {
        [$resolvedFrom, $resolvedTo] = [$resolvedTo, $resolvedFrom];
    }

    return [
        'date_from' => $resolvedFrom,
        'date_to' => $resolvedTo,
    ];
}

function build_query_url(string $path, array $params = []): string
{
    $filtered = array_filter(
        $params,
        static fn(mixed $value): bool => $value !== null && $value !== ''
    );

    $query = http_build_query($filtered);

    return $query === '' ? base_url($path) : base_url($path) . '?' . $query;
}

function fetch_team_categories(int $teamId, bool $includeInactive = false): array
{
    try {
        $sql = 'SELECT id, team_id, parent_id, category_name, category_code, sort_order, is_active
                FROM risk_categories
                WHERE team_id = :team_id';

        if (!$includeInactive) {
            $sql .= ' AND is_active = 1';
        }

        $sql .= ' ORDER BY sort_order ASC, category_name ASC';

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute(['team_id' => $teamId]);

        return $stmt->fetchAll();
    } catch (Throwable) {
        return [];
    }
}

function flatten_category_tree(array $categories, ?int $teamId = null, int $excludeId = 0): array
{
    $items = [];

    foreach ($categories as $category) {
        if ($teamId !== null && (int) ($category['team_id'] ?? 0) !== $teamId) {
            continue;
        }

        if ($excludeId > 0 && (int) ($category['id'] ?? 0) === $excludeId) {
            continue;
        }

        $items[] = $category;
    }

    usort(
        $items,
        static function (array $left, array $right): int {
            $sortComparison = ((int) ($left['sort_order'] ?? 0)) <=> ((int) ($right['sort_order'] ?? 0));
            if ($sortComparison !== 0) {
                return $sortComparison;
            }

            return strcmp((string) ($left['category_name'] ?? ''), (string) ($right['category_name'] ?? ''));
        }
    );

    $childrenByParent = [];
    foreach ($items as $item) {
        $parentKey = (int) ($item['parent_id'] ?? 0);
        $childrenByParent[$parentKey][] = $item;
    }

    $flattened = [];
    $walk = static function (int $parentId, int $depth) use (&$walk, &$flattened, $childrenByParent): void {
        foreach ($childrenByParent[$parentId] ?? [] as $child) {
            $child['depth'] = $depth;
            $flattened[] = $child;
            $walk((int) $child['id'], $depth + 1);
        }
    };

    $walk(0, 0);

    return $flattened;
}

function category_option_label(array $category): string
{
    $prefix = str_repeat('— ', (int) ($category['depth'] ?? 0));
    $code = trim((string) ($category['category_code'] ?? ''));
    $name = trim((string) ($category['category_name'] ?? ''));

    return trim($prefix . ($code !== '' ? $code . ' - ' : '') . $name);
}

function fetch_department_heads(): array
{
    try {
        $stmt = Database::connection()->query(
            "SELECT u.id, u.full_name, u.head_level, d.department_name
             FROM users u
             LEFT JOIN departments d ON d.id = u.department_id
             INNER JOIN roles r ON r.id = u.role_id
             WHERE u.is_active = 1
               AND r.role_code = 'DEPARTMENT_HEAD'
             ORDER BY d.department_name ASC, u.full_name ASC"
        );

        return $stmt->fetchAll();
    } catch (Throwable) {
        return [];
    }
}

function fetch_department_head_users(bool $includeInactive = false): array
{
    try {
        $sql = "SELECT
                    u.id,
                    u.full_name,
                    u.head_level,
                    u.is_active,
                    u.department_id,
                    d.department_name
                FROM users u
                LEFT JOIN departments d ON d.id = u.department_id
                INNER JOIN roles r ON r.id = u.role_id
                WHERE r.role_code = 'DEPARTMENT_HEAD'";

        if (!$includeInactive) {
            $sql .= ' AND u.is_active = 1';
        }

        $sql .= ' ORDER BY d.department_name ASC, u.full_name ASC';

        $stmt = Database::connection()->query($sql);

        return $stmt->fetchAll();
    } catch (Throwable) {
        return [];
    }
}

function fetch_team_department_visibility_entries(): array
{
    try {
        $stmt = Database::connection()->query(
            "SELECT
                tdv.id,
                tdv.team_id,
                tdv.department_id,
                tdv.viewer_user_id,
                tdv.visibility_type,
                tdv.is_active,
                t.team_code,
                t.team_name,
                d.department_name,
                u.full_name AS viewer_name
             FROM team_department_visibility tdv
             INNER JOIN teams t ON t.id = tdv.team_id
             INNER JOIN departments d ON d.id = tdv.department_id
             INNER JOIN users u ON u.id = tdv.viewer_user_id
             ORDER BY tdv.is_active DESC, t.team_code ASC, d.department_name ASC, u.full_name ASC"
        );

        return $stmt->fetchAll();
    } catch (Throwable) {
        return [];
    }
}

function fetch_report_severity_history(int $reportId): array
{
    try {
        $stmt = Database::connection()->prepare(
            'SELECT
                rsh.changed_at,
                rsh.changed_role_code,
                rsh.change_reason,
                old_sl.level_code AS old_level_code,
                new_sl.level_code AS new_level_code,
                u.full_name
             FROM report_severity_histories rsh
             LEFT JOIN severity_levels old_sl ON old_sl.id = rsh.old_severity_id
             LEFT JOIN severity_levels new_sl ON new_sl.id = rsh.new_severity_id
             LEFT JOIN users u ON u.id = rsh.changed_by_user_id
             WHERE rsh.report_id = :report_id
             ORDER BY rsh.id DESC'
        );
        $stmt->execute(['report_id' => $reportId]);

        return $stmt->fetchAll();
    } catch (Throwable) {
        return [];
    }
}

function fetch_assignment_route_logs(int $assignmentId): array
{
    try {
        $stmt = Database::connection()->prepare(
            'SELECT
                arl.created_at,
                arl.route_action,
                arl.route_reason,
                arl.route_note,
                from_user.full_name AS from_user_name,
                to_user.full_name AS to_user_name,
                t.team_code,
                d.department_name
             FROM assignment_route_logs arl
             LEFT JOIN users from_user ON from_user.id = arl.from_user_id
             LEFT JOIN users to_user ON to_user.id = arl.to_user_id
             LEFT JOIN teams t ON t.id = arl.to_team_id
             LEFT JOIN departments d ON d.id = arl.to_department_id
             WHERE arl.assignment_id = :assignment_id
             ORDER BY arl.id DESC'
        );
        $stmt->execute(['assignment_id' => $assignmentId]);

        return $stmt->fetchAll();
    } catch (Throwable) {
        return [];
    }
}

function client_ip_address(): ?string
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;

    if (!is_string($ip) || trim($ip) === '') {
        return null;
    }

    return mb_substr(trim($ip), 0, 45);
}

function client_user_agent(): ?string
{
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

    if (!is_string($userAgent) || trim($userAgent) === '') {
        return null;
    }

    return mb_substr(trim($userAgent), 0, 500);
}

function audit_log(
    string $action,
    string $entityType,
    string|int $entityId,
    ?array $detail = null,
    ?int $userId = null,
    ?PDO $pdo = null
): void {
    try {
        $connection = $pdo ?? Database::connection();
        $actorId = $userId;

        if ($actorId === null) {
            $user = Auth::user();
            $actorId = isset($user['id']) ? (int) $user['id'] : null;
        }

        $stmt = $connection->prepare(
            'INSERT INTO audit_logs (user_id, action, entity_type, entity_id, detail_json, ip_address, user_agent)
             VALUES (:user_id, :action, :entity_type, :entity_id, :detail_json, :ip_address, :user_agent)'
        );
        $stmt->execute([
            'user_id' => $actorId,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => (string) $entityId,
            'detail_json' => $detail !== null ? json_encode($detail, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
            'ip_address' => client_ip_address(),
            'user_agent' => client_user_agent(),
        ]);
    } catch (Throwable) {
        // Ignore audit failures to avoid blocking the main workflow.
    }
}

function report_status_label(string $status): string
{
    return match ($status) {
        'pending' => 'รอรับเรื่อง',
        'admin_review' => 'Admin กำลังพิจารณา',
        'in_progress' => 'กำลังดำเนินการ',
        'completed' => 'เสร็จสิ้น',
        default => $status,
    };
}
