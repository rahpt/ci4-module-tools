<?php

namespace Rahpt\Ci4ModuleTools\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class ModuleInstall extends BaseCommand
{
    protected $group = 'Modules';
    protected $name = 'module:install';
    protected $description = 'Runs the installation logic for a specific module.';
    protected $usage = 'module:install <ModuleName>';

    public function run(array $params)
    {
        if (empty($params)) {
            CLI::error("Module name is required.");
            return;
        }

        $module = ucfirst($params[0]);
        $registry = service('modules');
        $availableModules = $registry->getAvailableModules();

        if (!isset($availableModules[$module])) {
            CLI::error("Module {$module} not found.");
            return;
        }

        $moduleInfo = $availableModules[$module];

        // Validate and Install dependencies
        if (isset($moduleInfo['require']) && is_array($moduleInfo['require'])) {
            foreach ($moduleInfo['require'] as $requiredPkg => $version) {
                // Se a chave for numérica, o pacote é o valor. Caso contrário, a chave é o pacote.
                $package = is_numeric($requiredPkg) ? $version : $requiredPkg;

                // Se o pacote contém '/', tratamos como dependência do Composer
                if (strpos($package, '/') !== false) {
                    CLI::write("📦 Checking composer dependency: {$package}...", 'yellow');

                    // Verifica se o pacote já está instalado visualizando o vendor (simplificado)
                    // Ou simplesmente executa o require que o composer resolve o resto
                    $composer = 'composer';
                    // Tenta localizar o composer.phar se o comando 'composer' falhar (opcional)

                    $command = "{$composer} require {$package}";
                    passthru($command, $returnVar);

                    if ($returnVar !== 0) {
                        CLI::error("❌ Failed to install composer package: {$package}");
                        return;
                    }
                } else {
                    // Trata como dependência de outro módulo interno
                    if (!isset($availableModules[$package]) || !($availableModules[$package]['active'] ?? false)) {
                        CLI::error("Dependency error: Module '{$package}' is required by '{$module}' and must be active.");
                        return;
                    }
                }
            }
        }

        $config = config(\Rahpt\Ci4Module\Config\Modules::class);
        $class = $config->baseNamespace . "\\{$module}\\Config\\Module";

        if (!class_exists($class)) {
            CLI::error("Module Config class not found: {$class}");
            return;
        }

        CLI::write("🚀 Installing module {$module}...", 'blue');

        try {
            $instance = new $class();
            if (method_exists($instance, 'install')) {
                $instance->install();
                CLI::write("✔ Module {$module} installed successfully.", 'green');
            } else {
                CLI::write("ℹ No install method found for module {$module}.", 'yellow');
            }
        } catch (\Exception $e) {
            CLI::error("Error installing module {$module}: " . $e->getMessage());
        }
    }
}
