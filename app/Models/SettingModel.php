<?php

namespace App\Models;

use CodeIgniter\Model;

class SettingModel extends Model
{
    protected $table = 'settings';
    protected $primaryKey = 'id';
    protected $allowedFields = ['setting_key', 'setting_value'];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    /**
     * Get a setting value by key
     */
    public function getValue($key, $default = null)
    {
        $setting = $this->where('setting_key', $key)->first();
        return $setting ? $setting['setting_value'] : $default;
    }

    /**
     * Set a setting value by key
     */
    public function setValue($key, $value)
    {
        $setting = $this->where('setting_key', $key)->first();

        if ($setting) {
            return $this->update($setting['id'], ['setting_value' => $value]);
        } else {
            return $this->insert(['setting_key' => $key, 'setting_value' => $value]);
        }
    }
}
