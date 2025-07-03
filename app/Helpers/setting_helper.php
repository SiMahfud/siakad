<?php

if (!function_exists('get_setting')) {
    /**
     * Retrieves a setting value from the database.
     *
     * @param string $key The key of the setting to retrieve.
     * @param mixed|null $default The default value to return if the setting is not found.
     * @return mixed The setting value or the default value.
     */
    function get_setting(string $key, $default = null)
    {
        $settingModel = new \App\Models\SettingModel();
        $value = $settingModel->getSetting($key);

        return $value !== null ? $value : $default;
    }
}

if (!function_exists('get_all_settings')) {
    /**
     * Retrieves all settings from the database as an associative array.
     *
     * @return array An associative array of settings (key => value).
     */
    function get_all_settings(): array
    {
        $settingModel = new \App\Models\SettingModel();
        return $settingModel->getAllSettings();
    }
}
