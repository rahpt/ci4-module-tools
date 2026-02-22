<?php

namespace Rahpt\Ci4ModuleTools\Support;

use CodeIgniter\CLI\CLI;
use Config\Database;

class ModuleMigrationHelper
{
    public static function CreateMigration(string $module, string $tableName): bool
    {
        $db = Database::connect();
        $tableExists = ModuleTableUtils::tableExists($tableName);

        if (!$tableExists) {
            CLI::write("⚠ Table '{$tableName}' does not exist. Generating base migration.", 'yellow');
            $content = self::getContentEmpty($module, $tableName);
        } else {
            $content = self::getContentFields($module, $tableName);
        }

        $filePath = TemplateHelper::ModuleCreateFolder($module, 'Database/Migrations');
        $timestamp = date('Y-m-d-His');
        $fileName = "{$timestamp}_create_{$tableName}_table.php";

        file_put_contents($filePath . $fileName, $content);
        CLI::write("✔ Migration created: Modules/{$module}/Database/Migrations/{$fileName}", 'green');

        return $tableExists;
    }

    protected static function getContentEmpty(string $module, string $tableName): string
    {
        $forgeFields = [
            'id' => ['type' => 'INT', 'null' => false, 'unsigned' => true, 'auto_increment' => true],
            'name' => ['type' => 'VARCHAR', 'null' => false, 'constraint' => 100],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ];

        return TemplateHelper::generateContentFromTemplate(
            TemplateHelper::getTemplatePath('Migration.tpl'),
            [
                'ModuleName' => ucfirst($module),
                'TableName' => strtolower($tableName),
                'Fields' => ModuleTableUtils::formatArrayExport($forgeFields),
                'PrimaryKeys' => ModuleTableUtils::formatPrimaryKeys(['id']),
            ]
        );
    }

    protected static function getContentFields(string $module, string $tableName): string
    {
        $db = Database::connect();
        $fieldData = $db->getFieldData($tableName);
        $primaryKeys = [];
        $forgeFields = [];

        foreach ($fieldData as $field) {
            $definition = [
                'type' => strtoupper($field->type),
                'null' => $field->nullable,
            ];

            if ($field->max_length) {
                $definition['constraint'] = $field->max_length;
            }

            if (isset($field->primary_key) && $field->primary_key) {
                $definition['unsigned'] = true;
                $definition['auto_increment'] = true;
                $primaryKeys[] = $field->name;
            }

            $forgeFields[$field->name] = $definition;
        }

        return TemplateHelper::generateContentFromTemplate(
            TemplateHelper::getTemplatePath('Migration.tpl'),
            [
                'ModuleName' => ucfirst($module),
                'TableName' => strtolower($tableName),
                'Fields' => ModuleTableUtils::formatArrayExport($forgeFields),
                'PrimaryKeys' => ModuleTableUtils::formatPrimaryKeys($primaryKeys),
            ]
        );
    }

    /**
     * Executes migrations for a specific module namespace.
     */
    public static function runMigrations(string $namespace): bool
    {
        try {
            $migrations = \Config\Services::migrations();
            $migrations->setNamespace($namespace);

            if ($migrations->latest()) {
                log_message('info', "ModuleMigrationHelper: Migrations executed for {$namespace}");
                return true;
            }

            log_message('debug', "ModuleMigrationHelper: No migrations to run for {$namespace}");
            return true;
        } catch (\Throwable $e) {
            log_message('error', "ModuleMigrationHelper: Failed to run migrations for {$namespace}: " . $e->getMessage());
            return false;
        }
    }
}
