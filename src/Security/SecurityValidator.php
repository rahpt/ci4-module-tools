<?php

namespace Rahpt\Ci4ModuleTools\Security;

use Exception;
use Rahpt\Ci4ModuleTools\Config\ModuleTools;

/**
 * SecurityValidator - Validates URLs and files for security threats
 */
class SecurityValidator
{
    protected ModuleTools $config;

    public function __construct(?ModuleTools $config = null)
    {
        $this->config = $config ?? config(\Rahpt\Ci4ModuleTools\Config\ModuleTools::class);
    }

    /**
     * Validate URL for SSRF attacks
     * 
     * @throws Exception if URL is not safe
     */
    public function validateUrl(string $url): bool
    {
        // Check URL format
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new Exception('Invalid URL format');
        }

        // Parse URL
        $parsed = parse_url($url);
        
        if (!$parsed || !isset($parsed['scheme']) || !isset($parsed['host'])) {
            throw new Exception('Malformed URL');
        }

        // Check allowed schemes
        if (!in_array(strtolower($parsed['scheme']), $this->config->allowedSchemes)) {
            throw new Exception('URL scheme not allowed. Only ' . implode(', ', $this->config->allowedSchemes) . ' are permitted');
        }

        // Check file extension
        if (!str_ends_with(strtolower($url), '.zip')) {
            throw new Exception('URL must point to a .zip file');
        }

        // Resolve hostname to IP
        $host = $parsed['host'];
        $ip = gethostbyname($host);
        
        // Check for IP in blacklisted ranges
        if ($this->isBlacklistedIp($ip)) {
            throw new Exception('Access to private/internal networks is not allowed');
        }

        return true;
    }

    /**
     * Check if IP is in blacklisted ranges
     */
    public function isBlacklistedIp(string $ip): bool
    {
        foreach ($this->config->blacklistedIpRanges as $range) {
            if ($this->ipInRange($ip, $range)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Check if an IP is within a CIDR range
     */
    private function ipInRange(string $ip, string $cidr): bool
    {
        list($subnet, $mask) = explode('/', $cidr);
        
        $ip_long = ip2long($ip);
        $subnet_long = ip2long($subnet);
        $mask_long = -1 << (32 - (int)$mask);
        
        return ($ip_long & $mask_long) === ($subnet_long & $mask_long);
    }

    /**
     * Validate ZIP file structure and content
     * 
     * @throws Exception if ZIP is not safe
     */
    public function validateZipFile(string $zipPath): bool
    {
        // Check file exists
        if (!file_exists($zipPath)) {
            throw new Exception('ZIP file not found');
        }

        // Check file size
        $size = filesize($zipPath);
        if ($size > $this->config->maxZipSize) {
            throw new Exception('ZIP file exceeds maximum allowed size of ' . 
                $this->formatBytes($this->config->maxZipSize));
        }

        // Open ZIP
        $zip = new \ZipArchive();
        $result = $zip->open($zipPath);
        
        if ($result !== true) {
            throw new Exception('Failed to open ZIP file: ' . $this->getZipError($result));
        }

        try {
            // Check for path traversal attacks
            $this->checkZipPathTraversal($zip);
            
            // Validate required structure
            $this->checkRequiredStructure($zip);
            
            return true;
        } finally {
            $zip->close();
        }
    }

    /**
     * Check for path traversal attempts in ZIP
     * 
     * @throws Exception if path traversal detected
     */
    private function checkZipPathTraversal(\ZipArchive $zip): void
    {
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameIndex($i);
            
            // Check for directory traversal
            if (strpos($filename, '..') !== false) {
                throw new Exception('ZIP file contains path traversal attempt: ' . $filename);
            }
            
            // Check for absolute paths
            if (strpos($filename, '/') === 0 || preg_match('/^[A-Z]:\\\\/i', $filename)) {
                throw new Exception('ZIP file contains absolute path: ' . $filename);
            }
        }
    }

    /**
     * Check if ZIP contains required module structure
     * 
     * @throws Exception if required files are missing
     */
    private function checkRequiredStructure(\ZipArchive $zip): void
    {
        $foundFiles = [];
        
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameIndex($i);
            $foundFiles[] = $filename;
        }
        
        // Extract root folder name (modules usually have a root folder)
        $rootFolder = '';
        if (!empty($foundFiles)) {
            $firstFile = $foundFiles[0];
            $parts = explode('/', $firstFile);
            if (count($parts) > 1) {
                $rootFolder = $parts[0] . '/';
            }
        }
        
        // Check for required files
        foreach ($this->config->requiredStructure as $required) {
            $requiredPath = $rootFolder . $required;
            
            if (!in_array($requiredPath, $foundFiles)) {
                throw new Exception('ZIP file is missing required file: ' . $required);
            }
        }
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Get human-readable ZipArchive error message
     */
    private function getZipError(int $code): string
    {
        $errors = [
            \ZipArchive::ER_NOZIP => 'Not a valid ZIP archive',
            \ZipArchive::ER_INCONS => 'Inconsistent ZIP archive',
            \ZipArchive::ER_CRC => 'CRC error',
            \ZipArchive::ER_READ => 'Read error',
            \ZipArchive::ER_SEEK => 'Seek error',
        ];
        
        return $errors[$code] ?? 'Unknown error (code: ' . $code . ')';
    }
}
