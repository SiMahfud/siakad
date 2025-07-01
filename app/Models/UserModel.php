<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table            = 'users';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array'; // Can be 'object' or a custom class
    protected $useSoftDeletes   = false; // Set to true if you implement soft deletes

    protected $allowedFields    = [
        'username',
        'password',
        'full_name',
        'role_id',
        'is_active',
    ];

    // Dates
    protected $useTimestamps = true; // Ensure your 'users' table has created_at and updated_at
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    // protected $deletedField  = 'deleted_at'; // For soft deletes

    // Validation
    protected $validationRules = [
        'username'  => 'required|min_length[3]|max_length[100]|is_unique[users.username,id,{id}]',
        'full_name' => 'permit_empty|max_length[255]',
        'role_id'   => 'required|integer|is_not_unique[roles.id]',
        'is_active' => 'required|in_list[0,1]',
        'password'  => 'required|min_length[8]', // On create or when password is being changed
        'password_confirm' => 'matches[password]', // If password confirmation is used
    ];

    protected $validationMessages = [
        'username' => [
            'is_unique' => 'This username is already taken.',
        ],
        'role_id' => [
            'is_not_unique' => 'The selected role is invalid.',
        ],
        'password' => [
            'min_length' => 'Password must be at least 8 characters long.',
        ],
        'password_confirm' => [
            'matches' => 'Passwords do not match.',
        ]
    ];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert   = ['hashPassword'];
    protected $beforeUpdate   = ['hashPassword'];

    /**
     * Hashes the password before inserting or updating the user record.
     * Only hashes if a 'password' key is present in the data array.
     */
    protected function hashPassword(array $data): array
    {
        if (!isset($data['data']['password'])) {
            return $data;
        }

        $data['data']['password'] = password_hash($data['data']['password'], PASSWORD_DEFAULT);
        return $data;
    }

    /**
     * Verifies a given password against the user's hashed password.
     *
     * @param string $inputPassword The password to verify.
     * @param string $hashedPassword The hashed password from the database.
     */
    public function verifyPassword(string $inputPassword, string $hashedPassword): bool
    {
        return password_verify($inputPassword, $hashedPassword);
    }

    /**
     * Get user with their role name.
     */
    public function getUserWithRole(int $id)
    {
        return $this->select('users.*, roles.role_name')
                    ->join('roles', 'roles.id = users.role_id', 'left')
                    ->find($id);
    }

    public function getAllUsersWithRoles()
    {
         return $this->select('users.*, roles.role_name')
                    ->join('roles', 'roles.id = users.role_id', 'left')
                    ->orderBy('users.full_name', 'ASC')
                    ->findAll();
    }
}
