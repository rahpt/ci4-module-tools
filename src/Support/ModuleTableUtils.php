<?php

namespace Rahpt\Ci4ModuleTools\Support;

use Config\Database;

class ModuleTableUtils
{
    public static function formatArrayExport(array $data): string
    {
        $export = var_export($data, true);
        return preg_replace('/^/m', '            ', $export);
    }

    public static function formatPrimaryKeys(array $keys): string
    {
        if (empty($keys)) {
            return '';
        }
        $list = implode("','", $keys);
        return "        \$this->forge->addKey('{$list}', true);\n";
    }

    public static function getFieldsFromTable(string $tableName, array $ignore = ['id', 'created_at', 'updated_at', 'deleted_at']): array
    {
        try {
            $db = Database::connect();
            if (!$db->tableExists($tableName)) {
                return $ignore;
            }
            $fields = $db->getFieldNames($tableName);
            return array_values(array_filter($fields, fn($field) => !in_array($field, $ignore)));
        } catch (\Throwable $e) {
            return $ignore;
        }
    }

    public static function tableExists(string $tableName): bool
    {
        $db = Database::connect();
        return $db->tableExists($tableName);
    }

    public static function getValidationRulesFromTable(string $tableName): array
    {
        $db = Database::connect();
        if (!$db->tableExists($tableName)) {
            return [];
        }
        
        $fields = $db->getFieldData($tableName);
        $rules = [];

        foreach ($fields as $field) {
            if (in_array($field->name, ['id', 'created_at', 'updated_at', 'deleted_at'])) {
                continue;
            }

            $rule = '';
            if (!$field->nullable) {
                $rule .= 'required|';
            }

            $type = strtolower($field->type);
            if (strpos($type, 'int') !== false) {
                $rule .= 'integer';
            } elseif (strpos($type, 'char') !== false || strpos($type, 'text') !== false) {
                $rule .= 'max_length[' . ($field->max_length ?? 255) . ']';
            } elseif (strpos($type, 'date') !== false) {
                $rule .= 'valid_date';
            } elseif (strpos($field->name, 'email') !== false) {
                $rule .= 'valid_email';
            } else {
                $rule .= 'permit_empty';
            }

            $rules[$field->name] = rtrim($rule, '|');
        }

        return $rules;
    }
}
