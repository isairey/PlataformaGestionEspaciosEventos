<?php

namespace App\Models;

use CodeIgniter\Model;

class SignatoriesModel extends Model
{
    protected $table = 'signatories';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = ['template_name', 'field_key', 'field_label', 'field_value'];
    protected $useTimestamps = false;  // Disable timestamps since table may not have them

    /**
     * Get signatory value by template and field key
     */
    public function getValue($templateName, $fieldKey)
    {
        $result = $this->where('template_name', $templateName)
                       ->where('field_key', $fieldKey)
                       ->first();
        
        return $result['field_value'] ?? '';
    }

    /**
     * Get all signatories for a template
     */
    public function getByTemplate($templateName)
    {
        return $this->where('template_name', $templateName)->findAll();
    }

    /**
     * Update or insert signatory value
     */
    public function setValue($templateName, $fieldKey, $fieldLabel, $fieldValue)
    {
        $existing = $this->where('template_name', $templateName)
                        ->where('field_key', $fieldKey)
                        ->first();
        
        if ($existing) {
            // Update
            return $this->update($existing['id'], [
                'field_value' => $fieldValue
            ]);
        } else {
            // Insert
            return $this->insert([
                'template_name' => $templateName,
                'field_key' => $fieldKey,
                'field_label' => $fieldLabel,
                'field_value' => $fieldValue
            ]);
        }
    }

    /**
     * Batch update signatories
     */
    public function batchUpdate($templateName, $signatories)
    {
        foreach ($signatories as $fieldKey => $fieldValue) {
            $this->setValue($templateName, $fieldKey, $fieldKey, $fieldValue);
        }
        return true;
    }
}
