<?php

namespace App\Modules\ModuleManager\Controllers;

use App\Controllers\BaseController;
use Rahpt\Ci4ModuleTheme\ThemeManager;
use Rahpt\Ci4ModuleTools\Config\ModuleTools;
use Rahpt\Ci4ModuleTools\Security\SecurityValidator;
use Rahpt\Ci4Module\Validators\DependencyChecker;
use Rahpt\Ci4Module\Validators\ModuleStructureValidator;
use Exception;

class ModuleController extends BaseController
{
    protected ModuleTools $config;
    protected SecurityValidator $validator;
    protected DependencyChecker $dependencyChecker;
    protected ModuleStructureValidator $structureValidator;

    public function __construct()
    {
        $this->config = config(ModuleTools::class);
        $this->validator = new SecurityValidator($this->config);
        $this->dependencyChecker = new DependencyChecker();
        $this->structureValidator = new ModuleStructureValidator();
    }

    public function index()
    {
        $registry = service('modules');
        
        set_breadcrumb('Home', '/');
        set_breadcrumb('System', '#');
        set_breadcrumb('Modules', 'system/modules');

        return view('App\Modules\ModuleManager\Views\index', [
            'modules' => $registry->getAvailableModules(),
            'layout'  => ThemeManager::getModuleLayout('module-manager')
        ]);
    }

    public function toggle(string $slug)
    {
        $registry = service('modules');
        $availableModules = $registry->getAvailableModules();
        
        if (isset($availableModules[$slug])) {
            $currentStatus = $availableModules[$slug]['active'] ?? false;
            
            if ($currentStatus) {
                // Deactivating - no check needed
                $registry->deactivate($slug);
                log_message('info', "Module '{$slug}' deactivated");
            } else {
                // Activating - check dependencies
                $depCheck = $this->dependencyChecker->check($slug);
                
                if ($depCheck->hasIssues()) {
                    $errors = $this->dependencyChecker->getErrorMessages($depCheck);
                    log_message('warning', "Cannot activate '{$slug}': " . implode(', ', $errors));
                    return redirect()->back()->with('error', 'Cannot activate module: ' . implode(', ', $errors));
                }
                
                $registry->activate($slug);
                log_message('info', "Module '{$slug}' activated");
            }
        }

        return redirect()->back()->with('message', 'Module status updated.');
    }

    public function marketplace()
    {
        set_breadcrumb('Home', '/');
        set_breadcrumb('System', '#');
        set_breadcrumb('Modules', 'system/modules');
        set_breadcrumb('Marketplace');

        $registry = service('modules');
        $installedModules = $registry->getAvailableModules();
        $localModules = $this->scanLocalRepository();
        
        // Adicionar status de instalação e ativação para cada módulo local
        for ($i = 0; $i < count($localModules); $i++) {
            // Verificar se a pasta existe em app/Modules
            $installedPath = APPPATH . 'Modules' . DIRECTORY_SEPARATOR . $localModules[$i]['name'];
            $localModules[$i]['installed'] = is_dir($installedPath);
            $localModules[$i]['active'] = false;
            
            // Debug condicional
            if ($this->config->debugMode) {
                $localModules[$i]['debug_path'] = $installedPath;
                $localModules[$i]['debug_exists'] = is_dir($installedPath) ? 'SIM' : 'NÃO';
                $localModules[$i]['debug_comparisons'] = [];
            }
            
            // Verificar se está ativo
            if ($localModules[$i]['installed']) {
                foreach ($installedModules as $slug => $data) {
                    $installedFolder = basename($data['path'] ?? '');
                    
                    // Debug condicional
                    if ($this->config->debugMode) {
                        $localModules[$i]['debug_comparisons'][] = [
                            'slug' => $slug,
                            'installedFolder' => $installedFolder,
                            'data_name' => $data['name'] ?? 'N/A',
                            'match' => ($installedFolder === $localModules[$i]['name'] || 
                                       ($data['name'] ?? '') === $localModules[$i]['name'] ||
                                       strtolower($slug) === strtolower($localModules[$i]['name'])) ? 'SIM' : 'NÃO'
                        ];
                    }
                    
                    if ($installedFolder === $localModules[$i]['name'] || 
                        ($data['name'] ?? '') === $localModules[$i]['name'] ||
                        strtolower($slug) === strtolower($localModules[$i]['name'])) {
                        $localModules[$i]['active'] = $data['active'] ?? false;
                        
                        if ($this->config->debugMode) {
                            $localModules[$i]['debug_matched_with'] = $slug;
                        }
                        break;
                    }
                }
            }
        }

        $viewData = [
            'layout'       => ThemeManager::getModuleLayout('module-manager'),
            'localModules' => $localModules
        ];
        
        // Debug data condicional
        if ($this->config->debugMode) {
            $viewData['debug_installed'] = $installedModules;
        }

        return view('App\Modules\ModuleManager\Views\marketplace', $viewData);
    }

    public function install()
    {
        $url    = $this->request->getPost('url');
        $local  = $this->request->getPost('local');

        // Local installation
        if ($local) {
            try {
                log_message('info', "Attempting local module installation: {$local}");
                
                if ($this->installLocalModule($local)) {
                    if (class_exists(\Rahpt\Ci4ModuleNav\MenuRegistry::class)) {
                        \Rahpt\Ci4ModuleNav\MenuRegistry::clearCache();
                    }
                    log_message('info', "Module {$local} installed successfully");
                    return redirect()->to(base_url('system/modules/marketplace'))
                        ->with('message', "Módulo {$local} instalado com sucesso!");
                }
                
                log_message('error', "Failed to install local module: {$local}");
                return redirect()->back()->with('error', 'Falha ao instalar módulo local.');
                
            } catch (Exception $e) {
                log_message('error', "Exception during local installation: " . $e->getMessage());
                return redirect()->back()->with('error', 'Erro: ' . $e->getMessage());
            }
        }

        // Remote installation
        if (!$this->config->allowRemoteInstall) {
            log_message('warning', "Remote installation attempt blocked by configuration");
            return redirect()->back()->with('error', 'Instalação remota está desabilitada na configuração.');
        }

        if (empty($url)) {
            return redirect()->back()->with('error', 'URL não fornecida.');
        }

        try {
            // Validate URL for security
            log_message('info', "Validating URL for remote installation: {$url}");
            $this->validator->validateUrl($url);
            
            // Download and install
            if ($this->downloadModule($url)) {
                if (class_exists(\Rahpt\Ci4ModuleNav\MenuRegistry::class)) {
                    \Rahpt\Ci4ModuleNav\MenuRegistry::clearCache();
                }
                log_message('info', "Remote module installed successfully from: {$url}");
                return redirect()->to(base_url('system/modules/marketplace'))
                    ->with('message', 'Módulo instalado com sucesso! Não esqueça de ativá-lo.');
            }
            
        } catch (Exception $e) {
            log_message('error', "Remote installation failed: " . $e->getMessage());
            return redirect()->back()->with('error', 'Erro: ' . $e->getMessage());
        }

        log_message('error', "Failed to download module from: {$url}");
        return redirect()->back()->with('error', 'Não foi possível baixar o módulo.');
    }

    public function uninstall()
    {
        $moduleName = $this->request->getPost('module');
        
        if (!$moduleName) {
            return redirect()->back()->with('error', 'Nome do módulo não informado.');
        }

        $registry = service('modules');
        $installedModules = $registry->getAvailableModules();
        
        // Verificar se o módulo está ativo
        $isActive = false;
        foreach ($installedModules as $slug => $data) {
            if (strtolower($slug) === strtolower($moduleName) || 
                ($data['name'] ?? '') === $moduleName) {
                $isActive = $data['active'] ?? false;
                break;
            }
        }
        
        if ($isActive) {
            return redirect()->back()->with('error', 'Não é possível desinstalar módulos ativos. Desative o módulo primeiro.');
        }

        $targetPath = APPPATH . 'Modules/' . $moduleName;
        
        if (is_dir($targetPath)) {
            $this->recursiveDelete($targetPath);
            
            if (class_exists(\Rahpt\Ci4ModuleNav\MenuRegistry::class)) {
                \Rahpt\Ci4ModuleNav\MenuRegistry::clearCache();
            }
            
            return redirect()->to(base_url('system/modules/marketplace'))->with('message', "Módulo {$moduleName} desinstalado com sucesso!");
        }
        
        return redirect()->back()->with('error', 'Módulo não encontrado.');
    }

    private function scanLocalRepository(): array
    {
        $repoPath = $this->config->getLocalRepositoryPath();
        $modules = [];

        if (is_dir($repoPath)) {
            $folders = array_diff(scandir($repoPath), ['.', '..']);
            foreach ($folders as $folder) {
                if (is_dir($repoPath . $folder)) {
                    $modules[] = [
                        'name' => $folder,
                        'path' => $repoPath . $folder
                    ];
                }
            }
        }

        return $modules;
    }

    private function installLocalModule(string $folderName): bool
    {
        $source = $this->config->getLocalRepositoryPath() . $folderName;
        $target = APPPATH . 'Modules/' . $folderName;

        if (!is_dir($source)) {
            throw new Exception("Source directory not found: {$source}");
        }

        // Validate module structure
        $structureCheck = $this->structureValidator->validate($source);
        
        if ($structureCheck->hasErrors()) {
            $errors = implode(', ', $structureCheck->errors);
            log_message('error', "Module structure validation failed for {$folderName}: {$errors}");
            throw new Exception("Invalid module structure: {$errors}");
        }
        
        if ($structureCheck->hasWarnings()) {
            $warnings = implode(', ', $structureCheck->warnings);
            log_message('warning', "Module structure warnings for {$folderName}: {$warnings}");
        }

        $this->recursiveCopy($source, $target);
        return is_dir($target);
    }

    private function recursiveCopy(string $src, string $dst): void
    {
        if (is_dir($src)) {
            if (!is_dir($dst)) {
                if (!mkdir($dst, 0755, true)) {
                    throw new Exception("Failed to create directory: {$dst}");
                }
            }
            
            $files = scandir($src);
            foreach ($files as $file) {
                if ($file !== "." && $file !== "..") {
                    $this->recursiveCopy($src . DIRECTORY_SEPARATOR . $file, 
                                        $dst . DIRECTORY_SEPARATOR . $file);
                }
            }
        } elseif (file_exists($src)) {
            if (!copy($src, $dst)) {
                throw new Exception("Failed to copy file: {$src} to {$dst}");
            }
        }
    }

    private function recursiveDelete($dir)
    {
        if (!is_dir($dir)) return;
        
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            is_dir($path) ? $this->recursiveDelete($path) : unlink($path);
        }
        rmdir($dir);
    }

    private function downloadModule(string $url): bool
    {
        // Create stream context with timeout
        $context = stream_context_create([
            'http' => [
                'timeout' => $this->config->downloadTimeout,
                'max_redirects' => $this->config->maxRedirects,
                'user_agent' => 'CodeIgniter4-ModuleManager/1.0',
            ]
        ]);

        // Download with context
        $content = file_get_contents($url, false, $context);
        
        if ($content === false) {
            throw new Exception('Failed to download file from URL');
        }

        // Check size limit
        $size = strlen($content);
        if ($size > $this->config->maxZipSize) {
            throw new Exception('Downloaded file exceeds maximum size limit');
        }

        // Save to temp file
        $tempZip = WRITEPATH . 'temp_mod_' . uniqid() . '.zip';
        
        if (file_put_contents($tempZip, $content) === false) {
            throw new Exception('Failed to save downloaded file');
        }

        try {
            // Validate ZIP file
            $this->validator->validateZipFile($tempZip);

            // Extract ZIP
            $zip = new \ZipArchive();
            $result = $zip->open($tempZip);
            
            if ($result !== true) {
                throw new Exception('Failed to open ZIP file');
            }

            // Prepare target directory
            $target = APPPATH . 'Modules/';
            if (!is_dir($target)) {
                if (!mkdir($target, 0755, true)) {
                    throw new Exception('Failed to create Modules directory');
                }
            }

            // Extract
            if (!$zip->extractTo($target)) {
                $zip->close();
                throw new Exception('Failed to extract ZIP file');
            }
            
            $zip->close();
            
            // Cleanup
            if (file_exists($tempZip)) {
                unlink($tempZip);
            }
            
            return true;
            
        } catch (Exception $e) {
            // Cleanup on error
            if (file_exists($tempZip)) {
                unlink($tempZip);
            }
            throw $e;
        }
    }
}
