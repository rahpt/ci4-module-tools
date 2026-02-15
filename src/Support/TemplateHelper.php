<?php

namespace Rahpt\Ci4ModuleTools\Support;

use Rahpt\Ci4Module\ModuleRegistry;
use Rahpt\Ci4Module\Support\ModuleSetupHelper;

/**
 * TemplateHelper - Utility for template generation and scaffolding logic.
 */
class TemplateHelper
{
    public static function formatArray(array $data): string
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

    public static function generateContentFromTemplate(string $templatePath, array $vars): string
    {
        if (!is_file($templatePath)) {
            return '';
        }
        
        $template = file_get_contents($templatePath);

        foreach ($vars as $key => $value) {
            $template = str_replace("__{$key}__", $value, $template);
        }

        return $template;
    }

    public static function ModuleCreateFolder(string $module, string $context = '')
    {
        $modulePath = ucfirst($module);
        $namespace = ModuleSetupHelper::getNamespace();
        $baseFolder = str_replace(['App\\', '\\'], ['', DIRECTORY_SEPARATOR], $namespace);
        
        $path = APPPATH . $baseFolder . DIRECTORY_SEPARATOR . $modulePath . DIRECTORY_SEPARATOR;
        
        if ($context) {
            $path .= str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $context) . DIRECTORY_SEPARATOR;
        }

        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
        return $path;
    }

    public static function isShieldInstalled(): bool
    {
        return class_exists('CodeIgniter\\Shield\\Authentication\\Authentication');
    }

    public static function getTemplatePath(string $fileName): string
    {
        return dirname(__DIR__) . '/Generator/Templates/' . $fileName;
    }

    public static function updateModulesJson($module, $entity)
    {
        $registryData = ModuleRegistry::all($module);

        if (!isset($registryData[$module])) {
            $registryData[$module] = [
                'active' => true,
                'icon' => 'fas fa-folder-open',
                'label' => $module,
                'entities' => [],
            ];
        }

        $registryData[$module]['entities'][$entity] = [
            'active' => true,
            'label' => $entity . 's',
            'route' => '/' . strtolower($module) . '/' . strtolower($entity),
        ];

        ModuleRegistry::put($module, $registryData[$module]);
    }
}
