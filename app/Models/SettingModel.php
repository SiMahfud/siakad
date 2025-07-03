<?php

namespace App\Models;

use CodeIgniter\Model;

class SettingModel extends Model
{
    protected $table            = 'settings';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['key', 'value'];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Get a specific setting value by key.
     *
     * @param string $key The setting key.
     * @param mixed|null $default The default value to return if key not found.
     * @return mixed The setting value or default.
     */
    public function getSetting(string $key, $default = null)
    {
        $setting = $this->where('key', $key)->first();
        return $setting ? $setting['value'] : $default;
    }

    /**
     * Get all settings as an associative array (key => value).
     *
     * @return array
     */
    public function getAllSettings(): array
    {
        $settingsArray = [];
        $settings = $this->findAll();
        foreach ($settings as $setting) {
            $settingsArray[$setting['key']] = $setting['value'];
        }
        return $settingsArray;
    }

    /**
     * Save or update a setting.
     *
     * @param string $key The setting key.
     * @param mixed $value The setting value.
     * @return bool True on success, false on failure.
     */
    public function saveSetting(string $key, $value): bool
    {
        $data = ['key' => $key, 'value' => $value];
        $existing = $this->where('key', $key)->first();

        if ($existing) {
            return $this->update($existing['id'], ['value' => $value]);
        } else {
            return $this->insert($data, false); // Set returnID to false
        }
    }

    /**
     * Save multiple settings.
     * Expects an associative array ['key1' => 'value1', 'key2' => 'value2'].
     *
     * @param array $settings
     * @return bool Returns true if all settings were saved successfully.
     */
    public function saveSettings(array $settings): bool
    {
        $db = \Config\Database::connect();
        $db->transStart();

        foreach ($settings as $key => $value) {
            $this->saveSetting($key, $value);
        }

        $db->transComplete();
        return $db->transStatus();
    }
}
