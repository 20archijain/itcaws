Set-StrictMode -Version Latest
$ErrorActionPreference = "Stop"

$scriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$repoRoot = Resolve-Path (Join-Path $scriptDir "..")

Write-Host "[pre-commit] Running Angular lint..."
Push-Location (Join-Path $repoRoot "itcph2")
$oldCI = $env:CI
$env:CI = "1"
try {
    npx ng lint
    if ($LASTEXITCODE -ne 0) {
        throw "Angular lint failed."
    }
}
finally {
    if ($null -eq $oldCI) {
        Remove-Item Env:CI -ErrorAction SilentlyContinue
    }
    else {
        $env:CI = $oldCI
    }
    Pop-Location
}

Write-Host "[pre-commit] Running PHP lint on staged files..."
php (Join-Path $repoRoot "scripts/php_lint.php") --staged
if ($LASTEXITCODE -ne 0) {
    throw "PHP lint failed."
}

Write-Host "[pre-commit] All checks passed."
