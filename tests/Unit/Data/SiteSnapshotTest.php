<?php

declare(strict_types=1);

namespace DevMode\Tests\Unit\Data;

use Brain\Monkey;
use Brain\Monkey\Functions;
use DevMode\Data\SiteSnapshot;
use PHPUnit\Framework\TestCase;

class SiteSnapshotTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function testFormatFileSizeReturnsCorrectUnits(): void
    {
        $reflection = new \ReflectionClass(SiteSnapshot::class);
        $method = $reflection->getMethod('formatFileSize');
        $method->setAccessible(true);

        // Test bytes
        $this->assertEquals('0 B', $method->invoke(null, 0));
        $this->assertEquals('500 B', $method->invoke(null, 500));

        // Test KB
        $this->assertEquals('1 KB', $method->invoke(null, 1024));
        $this->assertEquals('1.5 KB', $method->invoke(null, 1536));

        // Test MB
        $this->assertEquals('1 MB', $method->invoke(null, 1024 * 1024));
        $this->assertEquals('5.5 MB', $method->invoke(null, (int)(5.5 * 1024 * 1024)));

        // Test GB
        $this->assertEquals('1 GB', $method->invoke(null, 1024 * 1024 * 1024));
    }

    public function testFormatFileSizeHandlesFalse(): void
    {
        $reflection = new \ReflectionClass(SiteSnapshot::class);
        $method = $reflection->getMethod('formatFileSize');
        $method->setAccessible(true);

        $this->assertEquals('0 B', $method->invoke(null, false));
    }

    public function testGetDebugLogInfoReturnsExpectedStructure(): void
    {
        // Mock WP_CONTENT_DIR
        if (!defined('WP_CONTENT_DIR')) {
            define('WP_CONTENT_DIR', sys_get_temp_dir());
        }

        $result = SiteSnapshot::getDebugLogInfo();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('exists', $result);
        $this->assertArrayHasKey('size', $result);
        $this->assertArrayHasKey('path', $result);
        $this->assertIsBool($result['exists']);
        $this->assertIsString($result['size']);
        $this->assertIsString($result['path']);
    }

    public function testFormatForClipboardReturnsString(): void
    {
        // Mock WordPress functions
        Functions\when('get_bloginfo')->justReturn('6.0');
        Functions\when('get_site_url')->justReturn('https://example.com');
        Functions\when('get_home_url')->justReturn('https://example.com');
        Functions\when('get_option')->justReturn([]);
        Functions\when('is_multisite')->justReturn(false);

        // Mock wp_get_theme
        $mockTheme = new class
        {
            public function get($key)
            {
                return $key === 'Name' ? 'Test Theme' : '';
            }

            public function parent()
            {
                return null;
            }
        };
        Functions\when('wp_get_theme')->justReturn($mockTheme);

        // Mock $wpdb - method name matches WordPress convention
        // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
        $GLOBALS['wpdb'] = new class
        {
            // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
            public function db_version()
            {
                return '8.0.0';
            }
        };

        // Mock constants
        if (!defined('WP_MEMORY_LIMIT')) {
            define('WP_MEMORY_LIMIT', '256M');
        }

        $result = SiteSnapshot::formatForClipboard();

        $this->assertIsString($result);
        $this->assertStringContainsString(':', $result);
    }

    public function testCollectReturnsArray(): void
    {
        // Mock WordPress functions
        Functions\when('get_bloginfo')->justReturn('6.0');
        Functions\when('get_site_url')->justReturn('https://example.com');
        Functions\when('get_home_url')->justReturn('https://example.com');
        Functions\when('get_option')->justReturn([]);
        Functions\when('is_multisite')->justReturn(false);

        $mockTheme = new class
        {
            public function get($key)
            {
                return $key === 'Name' ? 'Test Theme' : '';
            }

            public function parent()
            {
                return null;
            }
        };
        Functions\when('wp_get_theme')->justReturn($mockTheme);

        // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
        $GLOBALS['wpdb'] = new class
        {
            // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
            public function db_version()
            {
                return '8.0.0';
            }
        };

        $result = SiteSnapshot::collect();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('WordPress Version', $result);
        $this->assertArrayHasKey('PHP Version', $result);
        $this->assertArrayHasKey('MySQL Version', $result);
        $this->assertArrayHasKey('Active Theme', $result);
    }
}
