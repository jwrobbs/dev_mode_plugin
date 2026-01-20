<?php

declare(strict_types=1);

namespace DevMode\Tests\Unit\Config;

use Brain\Monkey;
use DevMode\Config\WpConfigEditor;
use PHPUnit\Framework\TestCase;

class WpConfigEditorTest extends TestCase
{
    private string $tempDir;
    private string $originalAbspath;

    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();

        // Create a temporary directory for test files
        $this->tempDir = sys_get_temp_dir() . '/dev_mode_test_' . uniqid();
        mkdir($this->tempDir, 0755, true);

        // Store original ABSPATH if defined
        if (defined('ABSPATH')) {
            $this->originalAbspath = ABSPATH;
        }
    }

    protected function tearDown(): void
    {
        // Clean up temp files
        $this->removeDirectory($this->tempDir);

        Monkey\tearDown();
        parent::tearDown();
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }

    public function testUpdateConstantReplacesExistingConstant(): void
    {
        $content = <<<'PHP'
<?php
define('WP_DEBUG', false);
define('OTHER_CONSTANT', 'value');
PHP;

        $reflection = new \ReflectionClass(WpConfigEditor::class);
        $method = $reflection->getMethod('updateConstant');
        $method->setAccessible(true);

        $result = $method->invoke(null, $content, 'WP_DEBUG', 'true');

        $this->assertStringContainsString("define('WP_DEBUG', true);", $result);
        $this->assertStringContainsString("define('OTHER_CONSTANT', 'value')", $result);
    }

    public function testUpdateConstantHandlesVariousFormats(): void
    {
        $testCases = [
            "define('WP_DEBUG', false);" => "define('WP_DEBUG', true);",
            'define("WP_DEBUG", false);' => "define('WP_DEBUG', true);",
            "define( 'WP_DEBUG' , false );" => "define('WP_DEBUG', true);",
            "define('WP_DEBUG',true);" => "define('WP_DEBUG', true);",
        ];

        $reflection = new \ReflectionClass(WpConfigEditor::class);
        $method = $reflection->getMethod('updateConstant');
        $method->setAccessible(true);

        foreach ($testCases as $input => $expected) {
            $result = $method->invoke(null, $input, 'WP_DEBUG', 'true');
            $this->assertStringContainsString("define('WP_DEBUG', true);", $result, "Failed for input: $input");
        }
    }

    public function testFindInsertPointLocatesStopEditingComment(): void
    {
        $content = <<<'PHP'
<?php
define('DB_NAME', 'test');

/* That's all, stop editing! Happy publishing. */

require_once ABSPATH . 'wp-settings.php';
PHP;

        $reflection = new \ReflectionClass(WpConfigEditor::class);
        $method = $reflection->getMethod('findInsertPoint');
        $method->setAccessible(true);

        $position = $method->invoke(null, $content);

        // The position should be before the "That's all" comment
        $beforePosition = substr($content, 0, $position);
        $this->assertStringNotContainsString("That's all", $beforePosition);
    }

    public function testGetDebugSettingsReturnsBooleanValues(): void
    {
        $result = WpConfigEditor::getDebugSettings();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('WP_DEBUG', $result);
        $this->assertArrayHasKey('WP_DEBUG_LOG', $result);
        $this->assertArrayHasKey('WP_DEBUG_DISPLAY', $result);
        $this->assertIsBool($result['WP_DEBUG']);
        $this->assertIsBool($result['WP_DEBUG_LOG']);
        $this->assertIsBool($result['WP_DEBUG_DISPLAY']);
    }
}
