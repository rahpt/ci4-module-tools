<?php

namespace Rahpt\Ci4ModuleTools\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class ModulePublish extends BaseCommand
{
    protected $group       = 'Modules';
    protected $name        = 'module:publish';
    protected $description = 'Publica os layouts base para a pasta de views da aplicação.';
    protected $usage       = 'module:publish';

    public function run(array $params)
    {
        // Localização do pacote ci4-module-theme (assume-se que está na vendor ou como repo local)
        // No CI4, podemos usar o namespace para achar o caminho
        $reflector = new \ReflectionClass(\Rahpt\Ci4ModuleTheme\Config\Registrar::class);
        $sourcePath = dirname($reflector->getFileName(), 2) . '/Views/layouts';
        $destPath   = APPPATH . 'Views/layouts';

        if (!is_dir($destPath)) {
            mkdir($destPath, 0755, true);
        }

        $files = glob($sourcePath . '/*.php');

        foreach ($files as $source) {
            $fileName = basename($source);
            $dest = $destPath . DIRECTORY_SEPARATOR . $fileName;
            if (copy($source, $dest)) {
                CLI::write("✔ Layout '{$fileName}' publicado em app/Views/layouts/", 'green');
            }
        }

        CLI::write("✔ Publicação concluída!", 'blue');
    }
}
