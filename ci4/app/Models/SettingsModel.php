<?php

namespace App\Models;

use CodeIgniter\Model;

class SettingsModel extends Model
{
    protected $table = 'web_settings';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;

    protected $allowedFields = [
        'setting_key',
        'setting_value',
        'updated_at',
    ];

    protected $useTimestamps = false;

    /**
     * Get a setting value by key
     */
    public function get(string $key, $default = null)
    {
        $result = $this->where('setting_key', $key)->first();
        return $result ? $result['setting_value'] : $default;
    }

    /**
     * Set a setting value
     */
    public function set(string $key, $value): bool
    {
        $existing = $this->where('setting_key', $key)->first();

        if ($existing) {
            return $this->update($existing['id'], [
                'setting_value' => $value,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }

        return (bool) $this->insert([
            'setting_key' => $key,
            'setting_value' => $value,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Delete a setting
     */
    public function remove(string $key): bool
    {
        return $this->where('setting_key', $key)->delete();
    }

    /**
     * Get all settings as key-value array
     */
    public function getAll(): array
    {
        $settings = $this->findAll();
        $result = [];

        foreach ($settings as $setting) {
            $result[$setting['setting_key']] = $setting['setting_value'];
        }

        return $result;
    }
}
