<?php

function getRoles(): array {
    $rawRoles = $_SESSION['role'] ?? '';
    $roles = array_filter(array_map('trim', preg_split('/[\s,]+/', $rawRoles)));
    $roles = array_map('strtolower', $roles);
    return array_unique($roles);
}

function hasRole(string $role): bool {
    $roles = getRoles();
    return in_array(strtolower($role), $roles, true);
}

function normalizeRoles(string $roles): string {
    $allRoles = array_filter(array_map('trim', preg_split('/[\s,]+/', $roles)));
    $allRoles = array_map('strtolower', $allRoles);
    return implode(',', array_unique($allRoles));
}

function isAdmin(): bool {
    if (hasRole('admin')) {
        return true;
    }

    if (isset($_SESSION['username']) && strtolower(trim($_SESSION['username'])) === 'admin') {
        return true;
    }

    return false;
}

function isHr(): bool {
    return hasRole('hr') || hasRole('HR');
}
