<?php

namespace Rahpt\Ci4ModuleTools\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Rahpt\Ci4ModuleTools\Security\SecurityValidator;
use Rahpt\Ci4ModuleTools\Config\ModuleTools;

/**
 * Tests for SecurityValidator SSRF + ZIP hardening.
 */
class SecurityValidatorTest extends TestCase
{
    protected SecurityValidator $validator;
    protected ModuleTools $config;

    protected function setUp(): void
    {
        parent::setUp();
        $this->config    = new ModuleTools();
        $this->validator = new SecurityValidator($this->config);
    }

    // ---- SSRF / IP block tests ----

    public function testBlocksLoopbackIpv4(): void
    {
        $this->assertTrue($this->validator->isBlockedIp('127.0.0.1'));
    }

    public function testBlocksPrivateClassA(): void
    {
        $this->assertTrue($this->validator->isBlockedIp('10.0.0.1'));
    }

    public function testBlocksPrivateClassB(): void
    {
        $this->assertTrue($this->validator->isBlockedIp('172.16.0.1'));
        $this->assertTrue($this->validator->isBlockedIp('172.31.255.255'));
    }

    public function testBlocksPrivateClassC(): void
    {
        $this->assertTrue($this->validator->isBlockedIp('192.168.1.1'));
    }

    public function testBlocksLinkLocalIpv4(): void
    {
        $this->assertTrue($this->validator->isBlockedIp('169.254.169.254')); // AWS metadata
    }

    public function testBlocksLoopbackIpv6(): void
    {
        $this->assertTrue($this->validator->isBlockedIp('::1'));
    }

    public function testBlocksUlaIpv6(): void
    {
        $this->assertTrue($this->validator->isBlockedIp('fc00::1'));
        $this->assertTrue($this->validator->isBlockedIp('fd12:3456:789a::1'));
    }

    public function testBlocksLinkLocalIpv6(): void
    {
        $this->assertTrue($this->validator->isBlockedIp('fe80::1'));
    }

    public function testAllowsPublicIpv4(): void
    {
        $this->assertFalse($this->validator->isBlockedIp('8.8.8.8'));
        $this->assertFalse($this->validator->isBlockedIp('1.1.1.1'));
    }

    public function testAllowsPublicIpv6(): void
    {
        $this->assertFalse($this->validator->isBlockedIp('2001:4860:4860::8888')); // Google DNS
    }

    public function testRejectsInvalidUrlFormat(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid URL format');
        $this->validator->validateUrl('not-a-url');
    }

    public function testRejectsHttpScheme(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('scheme not allowed');
        $this->validator->validateUrl('http://example.com/module.zip');
    }

    public function testRejectsNonZipExtension(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('.zip');
        $this->validator->validateUrl('https://example.com/module.tar.gz');
    }

    public function testRejectsUserinfoInUrl(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('userinfo');
        $this->validator->validateUrl('https://user:pass@example.com/module.zip');
    }

    public function testRejectsNonAllowedPort(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('port');
        $this->validator->validateUrl('https://example.com:8080/module.zip');
    }

    // ---- ZIP validation tests ----

    public function testZipFileNotFoundThrows(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('ZIP file not found');
        $this->validator->validateZipFile('/nonexistent/path/to/module.zip');
    }

    public function testValidZipPassesValidation(): void
    {
        $zipPath = $this->createValidZip();

        try {
            $result = $this->validator->validateZipFile($zipPath);
            $this->assertTrue($result);
        } finally {
            @unlink($zipPath);
        }
    }

    public function testZipWithPathTraversalIsRejected(): void
    {
        $zipPath = $this->createZipWithEntry('../etc/passwd', 'evil content');

        try {
            $this->expectException(\Exception::class);
            $this->expectExceptionMessage('path traversal');
            $this->validator->validateZipFile($zipPath);
        } finally {
            @unlink($zipPath);
        }
    }

    public function testZipWithAbsolutePathIsRejected(): void
    {
        $zipPath = $this->createZipWithEntry('/etc/passwd', 'evil content');

        try {
            $this->expectException(\Exception::class);
            $this->expectExceptionMessage('absolute');
            $this->validator->validateZipFile($zipPath);
        } finally {
            @unlink($zipPath);
        }
    }

    public function testRedirectValidationBlocksInsecureScheme(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Unsafe redirect scheme');
        $this->validator->validateRedirectUrl(
            'https://good.com/module.zip',
            'ftp://attacker.com/payload.zip',
            1
        );
    }

    // ---- Helpers ----

    private function createValidZip(): string
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'test_module_') . '.zip';
        $zip     = new \ZipArchive();
        $zip->open($tmpFile, \ZipArchive::CREATE);
        $zip->addFromString('MyModule/Config/Module.php', '<?php // module');
        $zip->addFromString('MyModule/module.json', '{"name":"Test","slug":"test","version":"1.0.0"}');
        $zip->close();
        return $tmpFile;
    }

    private function createZipWithEntry(string $entryName, string $content): string
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'test_evil_') . '.zip';
        $zip     = new \ZipArchive();
        $zip->open($tmpFile, \ZipArchive::CREATE);
        $zip->addFromString($entryName, $content);
        $zip->close();
        return $tmpFile;
    }
}
