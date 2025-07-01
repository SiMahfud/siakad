<?php

if (!function_exists('is_logged_in')) {
    /**
     * Checks if a user is logged in.
     *
     * @return bool
     */
    function is_logged_in(): bool
    {
        return session()->get('is_logged_in') ?? false;
    }
}

if (!function_exists('current_user_id')) {
    /**
     * Gets the current logged-in user's ID.
     *
     * @return int|null
     */
    function current_user_id(): ?int
    {
        return session()->get('user_id');
    }
}

if (!function_exists('current_user_role_id')) {
    /**
     * Gets the current logged-in user's role ID.
     *
     * @return int|null
     */
    function current_user_role_id(): ?int
    {
        return session()->get('role_id');
    }
}

if (!function_exists('hasRole')) {
    /**
     * Checks if the current logged-in user has a specific role (by ID or name).
     * Role names are matched against a predefined map.
     *
     * @param string|int|array $roleIdentifier Role ID, role name, or array of role IDs/names.
     * @return bool
     */
    function hasRole($roleIdentifier): bool
    {
        if (!is_logged_in()) {
            return false;
        }

        $currentUserRoleId = current_user_role_id();
        if ($currentUserRoleId === null) {
            return false;
        }

        // Role Name to ID mapping (adjust IDs as per your database)
        // This map should ideally be consistent with your roles table / RoleSeeder
        $roleMap = [
            'administrator sistem' => 1,
            'staf tata usaha'    => 2,
            'kepala sekolah'     => 3,
            'guru'               => 4,
            'siswa'              => 5,
            'orang tua'          => 6,
        ];

        $allowedRoleIds = [];
        if (!is_array($roleIdentifier)) {
            $roleIdentifier = [$roleIdentifier];
        }

        foreach ($roleIdentifier as $identifier) {
            if (is_numeric($identifier)) {
                $allowedRoleIds[] = (int) $identifier;
            } elseif (is_string($identifier) && isset($roleMap[strtolower($identifier)])) {
                $allowedRoleIds[] = $roleMap[strtolower($identifier)];
            }
        }

        return in_array($currentUserRoleId, $allowedRoleIds, true);
    }
}

if (!function_exists('isAdmin')) {
    /**
     * Checks if the current user is an Administrator Sistem.
     *
     * @return bool
     */
    function isAdmin(): bool
    {
        return hasRole('Administrator Sistem') || hasRole(1);
    }
}

if (!function_exists('isStafTU')) {
    /**
     * Checks if the current user is a Staf Tata Usaha.
     *
     * @return bool
     */
    function isStafTU(): bool
    {
        return hasRole('Staf Tata Usaha') || hasRole(2);
    }
}

if (!function_exists('isKepalaSekolah')) {
    /**
     * Checks if the current user is a Kepala Sekolah.
     *
     * @return bool
     */
    function isKepalaSekolah(): bool
    {
        return hasRole('Kepala Sekolah') || hasRole(3);
    }
}

if (!function_exists('isGuru')) {
    /**
     * Checks if the current user is a Guru.
     * (This includes Wali Kelas as Wali Kelas is also a Guru)
     * @return bool
     */
    function isGuru(): bool
    {
        return hasRole('Guru') || hasRole(4);
    }
}

// Add more specific role checks as needed: isSiswa(), isOrangTua()
?>
