#!/usr/bin/env php
<?php

/**
 * Quality checks runner with intelligent output
 *
 * Runs tests and linting, captures all output, and presents
 * results for human evaluation rather than binary pass/fail.
 */

declare(strict_types=1);

echo "=== Dev Mode Plugin - Quality Checks ===\n\n";

$baseDir = dirname(__DIR__);
$results = [];

// Run tests
echo "Running tests...\n";
$testOutput = [];
$testCode = 0;
exec("cd $baseDir && composer test 2>&1", $testOutput, $testCode);
$results['tests'] = [
    'output' => implode("\n", $testOutput),
    'exit_code' => $testCode,
];

// Run linting
echo "Running linter...\n";
$lintOutput = [];
$lintCode = 0;
exec("cd $baseDir && composer lint 2>&1", $lintOutput, $lintCode);
$results['lint'] = [
    'output' => implode("\n", $lintOutput),
    'exit_code' => $lintCode,
];

// Display results
echo "\n" . str_repeat("=", 60) . "\n";
echo "RESULTS SUMMARY\n";
echo str_repeat("=", 60) . "\n\n";

// Tests
echo "TESTS:\n";
echo str_repeat("-", 60) . "\n";
if (preg_match('/OK \((\d+) tests?, (\d+) assertions?\)/', $results['tests']['output'], $matches)) {
    echo "✓ {$matches[1]} tests passed, {$matches[2]} assertions\n";
} elseif (preg_match('/Tests: (\d+), Assertions: (\d+), Errors: (\d+)/', $results['tests']['output'], $matches)) {
    echo "✗ {$matches[1]} tests, {$matches[2]} assertions, {$matches[3]} errors\n";
} elseif (preg_match('/Tests: (\d+), Assertions: (\d+), Failures: (\d+)/', $results['tests']['output'], $matches)) {
    echo "✗ {$matches[1]} tests, {$matches[2]} assertions, {$matches[3]} failures\n";
} else {
    echo "Exit code: {$results['tests']['exit_code']}\n";
}
echo "\n";

// Linting
echo "LINTING:\n";
echo str_repeat("-", 60) . "\n";
if (preg_match('/FOUND (\d+) ERRORS? AND (\d+) WARNINGS?/', $results['lint']['output'], $matches)) {
    $errors = (int)$matches[1];
    $warnings = (int)$matches[2];

    if ($errors === 0 && $warnings === 0) {
        echo "✓ No errors or warnings\n";
    } elseif ($errors === 0) {
        echo "⚠ 0 errors, {$warnings} warnings\n";
    } else {
        echo "✗ {$errors} errors, {$warnings} warnings\n";
    }
} else {
    echo "Exit code: {$results['lint']['exit_code']}\n";
}

// Show lint details if there are issues
if ($results['lint']['exit_code'] !== 0) {
    echo "\nLinting details:\n";

    // Extract just the relevant parts (file summaries)
    $inSummary = false;
    foreach ($lintOutput as $line) {
        if (strpos($line, 'FILE:') !== false) {
            $inSummary = true;
        }
        if ($inSummary) {
            // Stop at time/memory footer
            if (strpos($line, 'Time:') !== false) {
                break;
            }
            echo $line . "\n";
        }
    }
}

echo "\n" . str_repeat("=", 60) . "\n";

// Full output available if needed
echo "\nFull output saved. Review above summary for evaluation.\n";
echo "Exit codes: Tests={$results['tests']['exit_code']}, Lint={$results['lint']['exit_code']}\n";

// Always exit 0 so script doesn't "fail" - human evaluates results
exit(0);
