@echo off
echo Running comparison script...
cd /d "c:\Users\prote\Documents\ksf_bank_import\lib\ksf_ofxparser"
php compare-repos.php > DEEP_COMPARISON_REPORT.txt 2>&1
echo Done! Output saved to DEEP_COMPARISON_REPORT.txt
pause
