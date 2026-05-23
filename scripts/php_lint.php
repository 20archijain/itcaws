<?php
declare(strict_types=1);

/**
 * Lint PHP files quickly for local hooks and CI.
 *
 * Usage:
 *   php scripts/php_lint.php            # lint all tracked PHP files
 *   php scripts/php_lint.php --staged   # lint only staged PHP files
 */

const EXCLUDED_PREFIXES = array(
    '.git/',
    '.github/',
    'itcph2/',
    'uproots/php_libs/',
    'uproots/prods/',
    'node_modules/',
    'vendor/',
);

function getRepoRoot(): string
{
    $root = realpath(__DIR__ . DIRECTORY_SEPARATOR . '..');
    if ($root === false) {
        fwrite(STDERR, "Unable to resolve repository root.\n");
        exit(1);
    }

    return str_replace('\\', '/', $root);
}

function runCommand(string $command): array
{
    $output = array();
    $exitCode = 0;
    exec($command . ' 2>&1', $output, $exitCode);

    return array($exitCode, implode("\n", $output));
}

function shouldIncludePath(string $path): bool
{
    if (substr($path, -4) !== '.php') {
        return false;
    }

    // Keep generated backend service PHP files under Angular dist in scope.
    if (strpos($path, 'itcph2/dist/browser/services/') === 0) {
        return true;
    }

    foreach (EXCLUDED_PREFIXES as $prefix) {
        if (strpos($path, $prefix) === 0) {
            return false;
        }
    }

    return true;
}

function normalizePath(string $path): string
{
    return str_replace('\\', '/', trim($path));
}

function getTrackedPhpFiles(string $repoRoot): array
{
    list($exitCode, $output) = runCommand('git ls-files "*.php"');
    if ($exitCode !== 0) {
        fwrite(STDERR, "Failed to list tracked PHP files.\n$output\n");
        exit(1);
    }

    $files = array_filter(array_map('normalizePath', explode("\n", $output)));
    $files = array_values(array_filter($files, 'shouldIncludePath'));

    return array_values(array_filter($files, function ($file) use ($repoRoot) {
        return is_file($repoRoot . '/' . $file);
    }));
}

function getStagedPhpFiles(string $repoRoot): array
{
    list($exitCode, $output) = runCommand('git diff --cached --name-only --diff-filter=ACMR');
    if ($exitCode !== 0) {
        fwrite(STDERR, "Failed to list staged files.\n$output\n");
        exit(1);
    }

    $files = array_filter(array_map('normalizePath', explode("\n", $output)));
    $files = array_values(array_filter($files, 'shouldIncludePath'));

    return array_values(array_filter($files, function ($file) use ($repoRoot) {
        return is_file($repoRoot . '/' . $file);
    }));
}

function lintFiles(array $files, string $repoRoot): int
{
    if (count($files) === 0) {
        echo "No PHP files to lint.\n";
        return 0;
    }

    echo 'Linting ' . count($files) . " PHP file(s)...\n";
    $errors = 0;

    foreach ($files as $file) {
        $fullPath = $repoRoot . '/' . $file;
        $command = 'php -l ' . escapeshellarg($fullPath);
        list($exitCode, $output) = runCommand($command);

        if ($exitCode !== 0) {
            $errors++;
            echo "FAIL: {$file}\n{$output}\n\n";
            continue;
        }

        echo "OK: {$file}\n";
    }

    if ($errors > 0) {
        echo "\nPHP lint failed for {$errors} file(s).\n";
        return 1;
    }

    echo "\nPHP lint passed.\n";
    return 0;
}

$repoRoot = getRepoRoot();
chdir($repoRoot);

$useStagedOnly = in_array('--staged', $argv, true);
$files = $useStagedOnly ? getStagedPhpFiles($repoRoot) : getTrackedPhpFiles($repoRoot);
$exitCode = lintFiles($files, $repoRoot);
exit($exitCode);
