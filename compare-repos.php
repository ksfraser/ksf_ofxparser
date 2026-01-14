<?php
/**
 * Comprehensive OFX Parser Repository Comparison and Cleanup Script
 * 
 * Purpose: Compare ksf_ofxparser against other repos and delete identical code
 * Date: January 13, 2026
 */

$baseDir = __DIR__;
$ksfDir = $baseDir;
$otherRepos = [
    'jacques' => $baseDir . '/../jacques-ofxparser',
    'ofx4' => $baseDir . '/../ofx4',
    'ofx2' => $baseDir . '/../ofx2',
    'memhetcoban' => $baseDir . '/../memhetcoban-ofxparser',
    'phpofx' => $baseDir . '/../phpofx-master',
];

// Mapping: KSF path => other repos' path pattern
$fileMapping = [
    'src/Ksfraser/Parser.php' => 'lib/OfxParser/Parser.php',
    'src/Ksfraser/Ofx.php' => 'lib/OfxParser/Ofx.php',
    'src/Ksfraser/Utils.php' => 'lib/OfxParser/Utils.php',
    'src/Ksfraser/Entities/Transaction.php' => 'lib/OfxParser/Entities/Transaction.php',
    'src/Ksfraser/Entities/Status.php' => 'lib/OfxParser/Entities/Status.php',
    'src/Ksfraser/Entities/BankAccount.php' => 'lib/OfxParser/Entities/BankAccount.php',
    'src/Ksfraser/Entities/Investment.php' => 'lib/OfxParser/Entities/Investment.php',
    'src/Ksfraser/Entities/Payee.php' => 'lib/OfxParser/Entities/Payee.php',
    'src/Ksfraser/Ofx/Investment.php' => 'lib/OfxParser/Ofx/Investment.php',
];

$report = [];
$report[] = "# OFX Parser Deep Comparison Report";
$report[] = "**Date:** " . date('Y-m-d H:i:s');
$report[] = "**Purpose:** File-by-file, function-by-function comparison";
$report[] = "";
$report[] = "---";
$report[] = "";

foreach ($fileMapping as $ksfFile => $otherFilePath) {
    $ksfPath = $ksfDir . '/' . $ksfFile;
    
    if (!file_exists($ksfPath)) {
        continue;
    }
    
    $report[] = "## " . basename($ksfFile);
    $report[] = "";
    
    $ksfContent = file_get_contents($ksfPath);
    $ksfMethods = extractMethods($ksfContent);
    
    $report[] = "**KSF Methods:** " . count($ksfMethods);
    if (count($ksfMethods) > 0) {
        $report[] = "**Method Names:** " . implode(', ', array_keys($ksfMethods));
    }
    $report[] = "";
    
    foreach ($otherRepos as $repoName => $repoDir) {
        $otherPath = $repoDir . '/' . $otherFilePath;
        
        if (!file_exists($otherPath)) {
            $report[] = "### $repoName: **NOT FOUND** (`$otherFilePath`)";
            $report[] = "";
            continue;
        }
        
        $otherContent = file_get_contents($otherPath);
        $otherMethods = extractMethods($otherContent);
        
        $report[] = "### $repoName";
        $report[] = "";
        $report[] = "**Path:** `" . str_replace($baseDir . '/../', '', $otherPath) . "`";
        $report[] = "**Methods:** " . count($otherMethods);
        $report[] = "";
        
        $comparison = compareMethods($ksfMethods, $otherMethods);
        
        $report[] = "#### Analysis:";
        $report[] = "";
        $report[] = "- **Identical:** " . $comparison['identical'];
        $report[] = "- **Similar:** " . $comparison['similar'];
        $report[] = "- **Different:** " . $comparison['different'];
        $report[] = "- **KSF Only:** " . $comparison['ksf_only'];
        $report[] = "- **Other Only:** " . $comparison['other_only'];
        $report[] = "";
        
        if ($comparison['different'] > 0) {
            $report[] = "#### Differences Requiring Analysis:";
            $report[] = "";
            foreach ($comparison['details'] as $detail) {
                if ($detail['status'] === 'different') {
                    $report[] = "**`" . $detail['name'] . "`**";
                    $report[] = "- **Impact:** " . $detail['impact'];
                    $report[] = "- **Action:** " . $detail['action'];
                    $report[] = "";
                }
            }
        }
        
        $report[] = "---";
        $report[] = "";
    }
}

// Save report
file_put_contents($baseDir . '/DEEP_COMPARISON_REPORT.md', implode("\n", $report));
echo "Report generated: DEEP_COMPARISON_REPORT.md\n";

/**
 * Extract methods from PHP code
 */
function extractMethods($content) {
    $methods = [];
    
    // Match function definitions
    preg_match_all(
        '/(public|protected|private)\s+(?:static\s+)?function\s+(\w+)\s*\([^)]*\)(?:\s*:\s*[\w\?\\\\]+)?/s',
        $content,
        $matches,
        PREG_SET_ORDER | PREG_OFFSET_CAPTURE
    );
    
    foreach ($matches as $match) {
        $visibility = $match[1][0];
        $name = $match[2][0];
        $start = $match[0][1];
        
        // Find method body
        $braceCount = 0;
        $inMethod = false;
        $end = $start;
        
        for ($i = $start; $i < strlen($content); $i++) {
            if ($content[$i] === '{') {
                $braceCount++;
                $inMethod = true;
            } elseif ($content[$i] === '}') {
                $braceCount--;
                if ($inMethod && $braceCount === 0) {
                    $end = $i + 1;
                    break;
                }
            }
        }
        
        $methodCode = substr($content, $start, $end - $start);
        
        $methods[$name] = [
            'visibility' => $visibility,
            'name' => $name,
            'code' => $methodCode,
            'signature' => $match[0][0],
            'hash' => md5(normalizeCode($methodCode)),
        ];
    }
    
    return $methods;
}

/**
 * Normalize code for comparison (remove whitespace, comments)
 */
function normalizeCode($code) {
    // Remove comments
    $code = preg_replace('#/\*.*?\*/#s', '', $code);
    $code = preg_replace('#//.*$#m', '', $code);
    
    // Remove extra whitespace
    $code = preg_replace('/\s+/', ' ', $code);
    
    return trim($code);
}

/**
 * Compare two sets of methods
 */
function compareMethods($ksfMethods, $otherMethods) {
    $result = [
        'identical' => 0,
        'similar' => 0,
        'different' => 0,
        'ksf_only' => 0,
        'other_only' => 0,
        'details' => [],
    ];
    
    // Check KSF methods
    foreach ($ksfMethods as $name => $ksfMethod) {
        if (!isset($otherMethods[$name])) {
            $result['ksf_only']++;
            $result['details'][] = [
                'name' => $name,
                'status' => 'ksf_only',
                'impact' => 'KSF has enhanced functionality',
                'action' => 'Keep in KSF, not in other repo',
            ];
            continue;
        }
        
        $otherMethod = $otherMethods[$name];
        
        // Check if identical
        if ($ksfMethod['hash'] === $otherMethod['hash']) {
            $result['identical']++;
            $result['details'][] = [
                'name' => $name,
                'status' => 'identical',
                'impact' => 'No difference',
                'action' => 'DELETE from other repo',
            ];
        } 
        // Check signature similarity
        elseif (similarSignature($ksfMethod['signature'], $otherMethod['signature'])) {
            $result['similar']++;
            $result['details'][] = [
                'name' => $name,
                'status' => 'similar',
                'impact' => 'Minor differences (whitespace, type hints)',
                'action' => 'DELETE from other repo - functionally equivalent',
            ];
        }
        else {
            $result['different']++;
            $result['details'][] = [
                'name' => $name,
                'status' => 'different',
                'impact' => analyzeDifference($ksfMethod['code'], $otherMethod['code']),
                'action' => 'KEEP - requires detailed analysis',
            ];
        }
    }
    
    // Check methods only in other repo
    foreach ($otherMethods as $name => $method) {
        if (!isset($ksfMethods[$name])) {
            $result['other_only']++;
            $result['details'][] = [
                'name' => $name,
                'status' => 'other_only',
                'impact' => 'Other repo has different/extra functionality',
                'action' => 'KEEP - review if needed in KSF',
            ];
        }
    }
    
    return $result;
}

/**
 * Check if signatures are similar
 */
function similarSignature($sig1, $sig2) {
    // Normalize signatures
    $norm1 = normalizeSignature($sig1);
    $norm2 = normalizeSignature($sig2);
    
    return $norm1 === $norm2;
}

/**
 * Normalize function signature
 */
function normalizeSignature($sig) {
    // Remove type hints
    $sig = preg_replace('/:\s*[\w\?\\\\]+\s*$/', '', $sig);
    // Remove parameter types
    $sig = preg_replace('/(\w+)\s+\$/', '$', $sig);
    // Normalize whitespace
    $sig = preg_replace('/\s+/', ' ', $sig);
    
    return trim($sig);
}

/**
 * Analyze difference between two code blocks
 */
function analyzeDifference($code1, $code2) {
    $impacts = [];
    
    // Check for mb_convert_encoding vs utf8_encode
    if (strpos($code1, 'mb_convert_encoding') !== false && strpos($code2, 'utf8_encode') !== false) {
        $impacts[] = "KSF uses mb_convert_encoding (PHP 8.2+ compatible)";
    }
    
    // Check for type hints
    if (preg_match('/:\s*[\w\?\\\\]+/', $code1) && !preg_match('/:\s*[\w\?\\\\]+/', $code2)) {
        $impacts[] = "KSF has type hints (PHP 7.3+)";
    }
    
    // Check for additional functionality
    $code1Lines = substr_count($code1, "\n");
    $code2Lines = substr_count($code2, "\n");
    if ($code1Lines > $code2Lines * 1.5) {
        $impacts[] = "KSF has significantly more code (" . $code1Lines . " vs " . $code2Lines . " lines)";
    } elseif ($code2Lines > $code1Lines * 1.5) {
        $impacts[] = "Other repo has more code (" . $code2Lines . " vs " . $code1Lines . " lines)";
    }
    
    if (empty($impacts)) {
        $impacts[] = "Logic difference - requires manual review";
    }
    
    return implode('; ', $impacts);
}
