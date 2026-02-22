<?php

namespace Rahpt\Ci4ModuleTools\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Rahpt\Ci4ModuleTools\Support\TemplateHelper;

class HomeModularize extends BaseCommand
{
    protected $group = 'Modules';
    protected $name = 'module:modularize-home';
    protected $description = 'Transforma os arquivos padrão Home.php e welcome_message.php em um módulo modular.';

    public function run(array $params)
    {
        $moduleName = 'Home';
        $modulePath = APPPATH . 'Modules/' . $moduleName . '/';

        CLI::write("🚀 Iniciando modularização do Home...", 'blue');

        // 1. Criar estrutura de pastas
        if (!is_dir($modulePath)) {
            mkdir($modulePath, 0755, true);
        }
        $folders = ['Config', 'Controllers', 'Views'];
        foreach ($folders as $folder) {
            $path = $modulePath . $folder;
            if (!is_dir($path)) {
                mkdir($path, 0755, true);
            }
        }

        // 2. Processar Controlador
        $sourceController = APPPATH . 'Controllers/Home.php';
        $targetController = $modulePath . 'Controllers/Home.php';

        if (is_file($sourceController)) {
            CLI::write("📄 Processando controlador...", 'yellow');
            $content = file_get_contents($sourceController);
            $content = str_replace('namespace App\Controllers;', 'namespace App\Modules\Home\Controllers;', $content);
            $content = str_replace("return view('welcome_message');", "return view('App\Modules\Home\Views\welcome_message');", $content);

            // Garantir que estende BaseController e tem o use correto se necessário
            if (strpos($content, 'use App\Controllers\BaseController;') === false) {
                $content = str_replace('class Home extends BaseController', "use App\Controllers\BaseController;\n\nclass Home extends BaseController", $content);
            }

            file_put_contents($targetController, $content);
            CLI::write("✔ Controlador movido para o módulo.", 'green');
        }

        // 3. Processar View
        $sourceView = APPPATH . 'Views/welcome_message.php';
        $targetView = $modulePath . 'Views/welcome_message.php';

        if (is_file($sourceView)) {
            CLI::write("📄 Processando view...", 'yellow');
            $content = file_get_contents($sourceView);

            // Adicionar lógica de Login/Dashboard se não existir
            if (strpos($content, 'auth()->loggedIn()') === false) {
                $loginSnippet = <<<'EOD'
            <li class="menu-item hidden"><a href="#">Home</a></li>
            <?php if (auth()->loggedIn()): ?>
                <li class="menu-item hidden"><a href="<?= base_url('dashboard') ?>">Dashboard</a></li>
                <li class="menu-item hidden"><a href="<?= base_url('logout') ?>" style="color: #dc3545;">Sair</a></li>
            <?php else: ?>
                <li class="menu-item hidden"><a href="<?= base_url('login') ?>">Login</a></li>
            <?php endif; ?>
EOD;
                $content = str_replace('<li class="menu-item hidden"><a href="#">Home</a></li>', $loginSnippet, $content);
            }

            file_put_contents($targetView, $content);
            CLI::write("✔ View preparada com suporte a login.", 'green');
        }

        // 4. Criar Config/Module.php
        CLI::write("📄 Criando metadados do módulo...", 'yellow');
        $moduleConfig = <<<'PHP'
<?php

namespace App\Modules\Home\Config;

use Rahpt\Ci4Module\BaseModule;

class Module extends BaseModule
{
    public string $name        = 'Home';
    public string $label       = 'Módulo Home';
    public string $slug        = 'home';
    public string $version     = '1.0.0';
    public string $theme       = 'adminlte';
    public string $routePrefix = '/';

    public function menu(): array
    {
        return [];
    }

    public function activate(): void
    {
        $file = APPPATH . 'Config/Routes.php';
        if (!is_file($file)) return;

        $content = file_get_contents($file);
        $pattern = '/^(\s*)\$routes->get\(\s*[\'"]\/[\'"]\s*,\s*[\'"]Home::index[\'"]\s*\);/m';
        $replacement = '$1// [$routes->get(\'/\', \'Home::index\');] // Managed by Home Module';

        if (preg_match($pattern, $content)) {
            file_put_contents($file, preg_replace($pattern, $replacement, $content));
        }
    }

    public function deactivate(): void
    {
        $file = APPPATH . 'Config/Routes.php';
        if (!is_file($file)) return;

        $content = file_get_contents($file);
        $pattern = '/(\s*)\/\/ \[\$routes->get\([\'"]\/[\'"]\s*,\s*[\'"]Home::index[\'"]\s*\);\] \/\/ Managed by Home Module/m';
        $replacement = '$1$routes->get(\'/\', \'Home::index\');';

        if (preg_match($pattern, $content)) {
            file_put_contents($file, preg_replace($pattern, $replacement, $content));
        }
    }
}
PHP;
        file_put_contents($modulePath . 'Config/Module.php', $moduleConfig);

        // 5. Criar Config/Routes.php
        $moduleRoutes = <<<'PHP'
<?php

$routes->group('/', ['namespace' => 'App\Modules\Home\Controllers'], function($routes) {
    $routes->get('', 'Home::index');
});
PHP;
        file_put_contents($modulePath . 'Config/Routes.php', $moduleRoutes);

        // 6. Criar README.md
        $readme = "# Módulo Home\n\nGerado automaticamente pelo comando `module:modularize-home`.";
        file_put_contents($modulePath . 'README.md', $readme);

        // 7. Registrar no modules.json
        $registry = service('modules');
        $registry->put('home', [
            'active' => false, // Começa desativado para o usuário ativar via spark
            'createdAt' => date(DATE_ATOM),
        ]);

        CLI::write("✔ Módulo Home configurado e registrado com sucesso!", 'black', 'green');
        CLI::write("👉 Execute 'php spark module:activate home' para assumir o controle da rota raiz.", 'yellow');
    }
}
