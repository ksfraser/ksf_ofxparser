# Run PHPUnit tests and save output
cd $PSScriptRoot
php vendor/phpunit/phpunit/phpunit 2>&1 | Tee-Object -FilePath test-output.txt
Write-Host "Tests completed. Output saved to test-output.txt"
