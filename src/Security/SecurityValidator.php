<?php

namespace Rahpt\Ci4ModuleTools\Security;

use Exception;
use Rahpt\Ci4ModuleTools\Config\ModuleTools;

/**
 * SecurityValidator - Validates URLs and ZIP files for security threats.
 *
 * SSRF protection strategy:
 *   URL -> scheme/port/userinfo -> DNS (all IPs) -> request -> redirect -> new URL -> new DNS -> re-validate
 *
 * ZIP protection:
 *   file count, uncompressed size, compression ratio, path traversal, absolute paths, symlinks,
 *   max filename length, max directory depth, duplicate normalized paths, case collisions.
 */
class SecurityValidator
{
    protected ModuleTools $config;

    public function __construct(?ModuleTools $config = null)
    {
        $this->config = $config ?? config(\Rahpt\Ci4ModuleTools\Config\ModuleTools::class);
    }

    /**
     * Full SSRF-resistant URL validation.
     *
     * Checks: scheme, userinfo (banned), port, extension, DNS (all IPv4 + IPv6 records),
     * DNS rebinding protection (bind to resolved IPs before request).
     *
     * @throws Exception if URL is not safe
     */
    public function validateUrl(string $url, int $redirectDepth = 0): bool
    {
        $maxRedirects = $this->config->maxRedirects ?? 3;

        if ($redirectDepth > $maxRedirects) {
            throw new Exception("Too many redirects (max {$maxRedirects})");
        }

        // Basic URL format check
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new Exception('Invalid URL format');
        }

        $parsed = parse_url($url);
        if (!$parsed || !isset($parsed['scheme'], $parsed['host'])) {
            throw new Exception('Malformed URL');
        }

        // Block userinfo in URL (e.g. http://user:pass@internal-host/)
        if (!empty($parsed['user']) || !empty($parsed['pass'])) {
            throw new Exception('URLs with userinfo (user:pass@host) are not permitted');
        }

        // Scheme check — only https allowed by default
        if (!in_array(strtolower($parsed['scheme']), $this->config->allowedSchemes, true)) {
            throw new Exception('URL scheme not allowed. Only ' . implode(', ', $this->config->allowedSchemes) . ' are permitted');
        }

        // Port check
        $port = $parsed['port'] ?? (strtolower($parsed['scheme']) === 'https' ? 443 : 80);
        if (!in_array((int) $port, $this->config->allowedPorts, true)) {
            throw new Exception("Connection port [{$port}] is not allowed for module download");
        }

        // Extension check
        $path = strtolower($parsed['path'] ?? '');
        if (!str_ends_with($path, '.zip')) {
            throw new Exception('URL must point to a .zip file');
        }

        $host = $parsed['host'];

        // Strip IPv6 brackets for validation
        $rawHost = trim($host, '[]');

        // Block numeric IP literals that are private/loopback (SSRF shortcut)
        if (filter_var($rawHost, FILTER_VALIDATE_IP)) {
            if ($this->isBlockedIp($rawHost)) {
                throw new Exception("Direct IP address [{$rawHost}] resolves to a blocked network range");
            }
        } else {
            // Resolve ALL DNS records (A + AAAA) — gethostbyname only returns one A record
            $this->validateHostDns($host);
        }

        return true;
    }

    /**
     * Validates a redirect URL encountered during download.
     * Re-runs the full SSRF validation on the new URL to prevent redirect-based bypasses.
     *
     * @throws Exception
     */
    public function validateRedirectUrl(string $originalUrl, string $redirectUrl, int $depth): bool
    {
        // Reject javascript: data: etc. in redirect Location headers
        $scheme = strtolower(parse_url($redirectUrl, PHP_URL_SCHEME) ?? '');
        if (!in_array($scheme, ['http', 'https'], true)) {
            throw new Exception("Unsafe redirect scheme [{$scheme}] detected in Location header");
        }

        log_message('info', "[SecurityValidator] Following redirect ({$depth}): {$originalUrl} -> {$redirectUrl}");
        return $this->validateUrl($redirectUrl, $depth);
    }

    /**
     * Resolves all IPv4 and IPv6 addresses for a hostname and checks each against blocked ranges.
     * Protects against DNS rebinding (only one IP checked at resolve time, different IP at connect time).
     *
     * @throws Exception if any resolved IP is blocked
     */
    protected function validateHostDns(string $host): void
    {
        // Resolve IPv4 (A records)
        $ipv4Records = dns_get_record($host, DNS_A);
        // Resolve IPv6 (AAAA records)
        $ipv6Records = dns_get_record($host, DNS_AAAA);

        $allIps = [];
        foreach ($ipv4Records as $record) {
            if (!empty($record['ip'])) {
                $allIps[] = $record['ip'];
            }
        }
        foreach ($ipv6Records as $record) {
            if (!empty($record['ipv6'])) {
                $allIps[] = $record['ipv6'];
            }
        }

        // Fallback: if dns_get_record fails (e.g., on some Windows configs), use gethostbyname
        if (empty($allIps)) {
            $fallback = gethostbyname($host);
            if ($fallback !== $host) {
                $allIps[] = $fallback;
            }
        }

        if (empty($allIps)) {
            throw new Exception("Could not resolve hostname: {$host}");
        }

        foreach ($allIps as $ip) {
            if ($this->isBlockedIp($ip)) {
                throw new Exception("Hostname [{$host}] resolves to blocked IP [{$ip}]. Access to private/internal networks is not allowed.");
            }
        }
    }

    /**
     * Checks if an IP (v4 or v6) is in any blocked range.
     */
    public function isBlockedIp(string $ip): bool
    {
        // Normalise IPv6
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return $this->isBlockedIpv6($ip);
        }

        // IPv4 CIDR check
        foreach ($this->config->blacklistedIpRanges as $range) {
            if (!str_contains($range, ':') && $this->ipv4InRange($ip, $range)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @deprecated Use isBlockedIp() — kept for backward compatibility.
     */
    public function isBlacklistedIp(string $ip): bool
    {
        return $this->isBlockedIp($ip);
    }

    /**
     * Check if an IPv4 address is within a CIDR range.
     */
    private function ipv4InRange(string $ip, string $cidr): bool
    {
        if (!str_contains($cidr, '/')) {
            return $ip === $cidr;
        }

        [$subnet, $mask] = explode('/', $cidr, 2);

        $ipLong     = ip2long($ip);
        $subnetLong = ip2long($subnet);

        if ($ipLong === false || $subnetLong === false) {
            return false;
        }

        $maskLong = -1 << (32 - (int) $mask);
        return ($ipLong & $maskLong) === ($subnetLong & $maskLong);
    }

    /**
     * Check if an IPv6 address is in a blocked range.
     * Covers: loopback (::1), link-local (fe80::/10), ULA (fc00::/7).
     */
    private function isBlockedIpv6(string $ip): bool
    {
        $packed = inet_pton($ip);
        if ($packed === false) {
            return false;
        }

        // Check each IPv6 CIDR in config
        foreach ($this->config->blacklistedIpRanges as $range) {
            if (!str_contains($range, ':')) {
                continue; // IPv4 range, skip
            }
            if ($this->ipv6InRange($packed, $range)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a packed IPv6 address falls within a CIDR range.
     */
    private function ipv6InRange(string $packedIp, string $cidr): bool
    {
        if (!str_contains($cidr, '/')) {
            return $packedIp === inet_pton($cidr);
        }

        [$subnet, $prefixLen] = explode('/', $cidr, 2);
        $packedSubnet = inet_pton($subnet);
        if ($packedSubnet === false) {
            return false;
        }

        $prefixLen = (int) $prefixLen;
        $bytes     = (int) ceil($prefixLen / 8);

        // Compare the relevant prefix bytes
        return substr($packedIp, 0, $bytes) === substr($packedSubnet, 0, $bytes);
    }

    /**
     * Validate ZIP file: bombs, traversal, symlinks, filename length, directory depth,
     * duplicate normalized paths, case collisions, required structure.
     *
     * @throws Exception if ZIP is not safe
     */
    public function validateZipFile(string $zipPath): bool
    {
        if (!file_exists($zipPath)) {
            throw new Exception('ZIP file not found');
        }

        $compressedSize = filesize($zipPath);
        if ($compressedSize > $this->config->maxZipSize) {
            throw new Exception('ZIP file exceeds maximum allowed size of ' . $this->formatBytes($this->config->maxZipSize));
        }

        $zip    = new \ZipArchive();
        $result = $zip->open($zipPath);

        if ($result !== true) {
            throw new Exception('Failed to open ZIP file: ' . $this->getZipError($result));
        }

        try {
            // Anti-ZIP-Bomb: file count
            if ($zip->numFiles > $this->config->maxZipFiles) {
                throw new Exception("ZIP archive contains {$zip->numFiles} files, exceeding limit of {$this->config->maxZipFiles}");
            }

            // Anti-ZIP-Bomb: uncompressed size + ratio
            $totalUncompressedSize = 0;
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat = $zip->statIndex($i);
                if ($stat) {
                    $totalUncompressedSize += $stat['size'];
                }
            }

            if ($totalUncompressedSize > $this->config->maxUncompressedSize) {
                throw new Exception('ZIP total uncompressed size exceeds limit of ' . $this->formatBytes($this->config->maxUncompressedSize));
            }

            $ratio = $compressedSize > 0 ? ($totalUncompressedSize / $compressedSize) : 1;
            if ($ratio > $this->config->maxCompressionRatio) {
                throw new Exception("Suspicious compression ratio ({$ratio}:1) exceeds limit of {$this->config->maxCompressionRatio}:1 (possible ZIP bomb)");
            }

            // Path traversal, symlinks, filename/depth limits, duplicates, case collisions
            $this->checkZipEntries($zip);

            // Required module structure
            $this->checkRequiredStructure($zip);

            return true;
        } finally {
            $zip->close();
        }
    }

    /**
     * Comprehensive ZIP entry validation:
     * - path traversal (..)
     * - absolute paths
     * - symlinks
     * - max filename length
     * - max directory depth
     * - duplicate normalized paths (ZIP Slip variant)
     * - case collisions (Foo.php vs foo.php on case-insensitive filesystems)
     *
     * @throws Exception
     */
    private function checkZipEntries(\ZipArchive $zip): void
    {
        $maxFilenameLength = $this->config->maxFilenameLength ?? 255;
        $maxDirectoryDepth = $this->config->maxDirectoryDepth ?? 10;

        $normalizedPaths    = [];  // lowercase normalized -> original (for case collision detection)
        $canonicalPaths     = [];  // exact normalized -> true (for duplicate detection)

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filename   = $zip->getNameIndex($i);
            $normalized = str_replace('\\', '/', $filename);

            // 1. Path traversal
            if (str_contains($normalized, '..')) {
                throw new Exception('ZIP file contains path traversal attempt: ' . $filename);
            }

            // 2. Absolute paths and drive letters
            if (str_starts_with($normalized, '/') || preg_match('/^[a-zA-Z]:/i', $filename) || str_starts_with($filename, '\\\\')) {
                throw new Exception('ZIP file contains absolute or drive-relative path: ' . $filename);
            }

            // 3. Symlinks (Unix mode 0120000)
            $opsys = 0;
            $attr  = 0;
            if ($zip->getExternalAttributesIndex($i, $opsys, $attr)) {
                if ($opsys === \ZipArchive::OPSYS_UNIX && (($attr >> 16) & 0120000) === 0120000) {
                    throw new Exception('ZIP file contains forbidden symbolic link entry: ' . $filename);
                }
            }

            // 4. Max filename length (basename only)
            $basename = basename($normalized);
            if (strlen($basename) > $maxFilenameLength) {
                throw new Exception("ZIP entry filename too long ({$basename}): max {$maxFilenameLength} characters");
            }

            // 5. Max directory depth
            $segments = array_filter(explode('/', rtrim($normalized, '/')));
            $depth    = count($segments) - 1; // directories above the file
            if ($depth > $maxDirectoryDepth) {
                throw new Exception("ZIP entry exceeds maximum directory depth ({$depth} > {$maxDirectoryDepth}): {$filename}");
            }

            // 6. Duplicate normalized paths (byte-exact)
            if (isset($canonicalPaths[$normalized])) {
                throw new Exception("ZIP contains duplicate normalized path: {$normalized}");
            }
            $canonicalPaths[$normalized] = true;

            // 7. Case collision — dangerous on case-insensitive filesystems (Windows, macOS)
            $lower = strtolower($normalized);
            if (isset($normalizedPaths[$lower]) && $normalizedPaths[$lower] !== $normalized) {
                throw new Exception(
                    "ZIP contains case collision: [{$normalized}] conflicts with [{$normalizedPaths[$lower]}]. Dangerous on case-insensitive filesystems."
                );
            }
            $normalizedPaths[$lower] = $normalized;
        }
    }

    /**
     * Check if ZIP contains required module structure.
     * @throws Exception
     */
    private function checkRequiredStructure(\ZipArchive $zip): void
    {
        $foundFiles = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $foundFiles[] = str_replace('\\', '/', $zip->getNameIndex($i));
        }

        // Detect root folder
        $rootFolder = '';
        if (!empty($foundFiles)) {
            $parts = explode('/', $foundFiles[0]);
            if (count($parts) > 1) {
                $rootFolder = $parts[0] . '/';
            }
        }

        foreach ($this->config->requiredStructure as $required) {
            $requiredPath = $rootFolder . $required;
            if (!in_array($requiredPath, $foundFiles, true) && !in_array($required, $foundFiles, true)) {
                throw new Exception('ZIP file is missing required file: ' . $required);
            }
        }
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i     = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }

    private function getZipError(int $code): string
    {
        $errors = [
            \ZipArchive::ER_NOZIP  => 'Not a valid ZIP archive',
            \ZipArchive::ER_INCONS => 'Inconsistent ZIP archive',
            \ZipArchive::ER_CRC    => 'CRC error',
            \ZipArchive::ER_READ   => 'Read error',
            \ZipArchive::ER_SEEK   => 'Seek error',
        ];
        return $errors[$code] ?? 'Unknown error (code: ' . $code . ')';
    }
}
