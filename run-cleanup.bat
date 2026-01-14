@echo off
echo ================================================================
echo  CLEANUP SCRIPT - DELETE DUPLICATE CODE FROM OTHER REPOS
echo  Based on Deep Analysis Report (Line-by-Line Comparison)
echo ================================================================
echo.
echo This will:
echo  1. DELETE 14 completely duplicate files (Utils, Transaction, Status, etc)
echo  2. DELETE ~60 functionally equivalent methods across 4 repos
echo  3. KEEP only 8 methods with real differences
echo  4. ADD detailed impact analysis comments to remaining code
echo.
echo Key findings:
echo  - Other repos use deprecated utf8_encode (PHP 8.2+ incompatible)
echo  - Other repos MISSING createTags preprocessing (major feature gap)
echo  - Other repos MISSING error handling for malformed OFX (production bugs)
echo  - KSF is the only PHP 8.2+ compatible version
echo.
echo Press Ctrl+C to cancel, or
pause
echo.
echo Running cleanup...
cd /d "c:\Users\prote\Documents\ksf_bank_import\lib\ksf_ofxparser"
php cleanup-duplicate-code.php
echo.
echo ================================================================
echo  CLEANUP COMPLETE!
echo ================================================================
echo.
echo Review CLEANUP_LOG.txt for details.
echo Review DEEP_ANALYSIS_REPORT.md for technical analysis.
echo.
pause
