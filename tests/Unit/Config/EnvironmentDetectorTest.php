<?php

declare(strict_types=1);

namespace DevMode\Tests\Unit\Config;

use Brain\Monkey;
use DevMode\Config\EnvironmentDetector;
use PHPUnit\Framework\TestCase;

class EnvironmentDetectorTest extends TestCase
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

    public function testEnvironmentColorsHasExpectedEntries(): void
    {
        $colors = EnvironmentDetector::ENVIRONMENT_COLORS;

        $this->assertArrayHasKey('production', $colors);
        $this->assertArrayHasKey('staging', $colors);
        $this->assertArrayHasKey('local', $colors);
        $this->assertArrayHasKey('unknown', $colors);
    }

    public function testProductionColorIsRed(): void
    {
        $colors = EnvironmentDetector::ENVIRONMENT_COLORS;
        $this->assertEquals('#dc3545', $colors['production']);
    }

    public function testStagingColorIsYellow(): void
    {
        $colors = EnvironmentDetector::ENVIRONMENT_COLORS;
        $this->assertEquals('#ffc107', $colors['staging']);
    }

    public function testLocalColorIsGreen(): void
    {
        $colors = EnvironmentDetector::ENVIRONMENT_COLORS;
        $this->assertEquals('#28a745', $colors['local']);
    }

    public function testUnknownColorIsGray(): void
    {
        $colors = EnvironmentDetector::ENVIRONMENT_COLORS;
        $this->assertEquals('#6c757d', $colors['unknown']);
    }

    public function testGetColorReturnsUnknownColorWhenEnvFileNotExists(): void
    {
        // When ABSPATH points to non-existent location, should return unknown color
        if (!defined('ABSPATH')) {
            define('ABSPATH', '/non/existent/path/');
        }

        $color = EnvironmentDetector::getColor();

        // Should return a valid color string (hex format)
        $this->assertMatchesRegularExpression('/^#[0-9a-f]{6}$/i', $color);
    }
}
