<?php

namespace Rahpt\Ci4ModuleTools\Support;

/**
 * LocalMarketplaceScanner - Scans a local directory for available modules.
 */
class LocalMarketplaceScanner
{
    /**
     * Scans the configured local repository for modules.
     */
    public static function scan(): array
    {
        $config = config('ModuleTools');
        if (!$config) {
            log_message('error', 'LocalMarketplaceScanner: Config ModuleTools not found.');
            return [];
        }

        $repoPath = $config->getLocalRepositoryPath();

        if (!is_dir($repoPath)) {
            log_message('error', 'LocalMarketplaceScanner: Path is not a directory: ' . $repoPath);
            return [];
        }

        $modules = [];
        $items = scandir($repoPath);

        foreach ($items as $item) {
            if ($item === '.' || $item === '..')
                continue;

            $fullPath = rtrim($repoPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $item;

            if (is_dir($fullPath)) {
                $metadata = self::getModuleMetadata($fullPath);
                if ($metadata) {
                    $modules[] = $metadata;
                }
            }
        }

        return $modules;
    }

    /**
     * Reads metadata from a module folder.
     */
    private static function getModuleMetadata(string $path): ?array
    {
        $slug = basename($path);

        // Check for common module markers
        $hasModuleConfig = is_file($path . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . 'Module.php');
        $hasComposer = is_file($path . DIRECTORY_SEPARATOR . 'composer.json');

        if (!$hasModuleConfig && !$hasComposer) {
            return null;
        }

        $displayName = ucfirst($slug);
        $description = 'Módulo local encontrado em ' . $slug;
        $version = '1.0.0';
        $author = 'Local';

        // Try reading composer.json if available
        if ($hasComposer) {
            $data = json_decode(file_get_contents($path . DIRECTORY_SEPARATOR . 'composer.json'), true);
            if ($data) {
                $displayName = $data['display_name'] ?? $data['name'] ?? $displayName;
                $description = $data['description'] ?? $description;
                $version = $data['version'] ?? $version;
                if (isset($data['authors'][0]['name'])) {
                    $author = $data['authors'][0]['name'];
                }
            }
        }

        return [
            'module_name' => "local/{$slug}",
            'display_name' => $displayName,
            'description' => $description,
            'version' => $version,
            'author' => $author,
            'slug' => $slug,
            'zip_path' => $slug,
            'icon_path' => null,
            'is_active' => 1
        ];
    }
}
