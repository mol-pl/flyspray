<?php
/**
 * Helpers for depends graphs.
 */
require_once './depends.inc.php';

// cmd only
if(php_sapi_name() !== 'cli') {
	die('');
}

// Tests
$verbose_test = true;
$verbose_test = false;
function testWrapAndCut($input, $width, $maxLines, $expected) {
	global $verbose_test;

	$result = wrapAndCut($input, $width, $maxLines);
	$pass = $result === $expected ? "PASS" : "FAIL";
	echo "\nTest (maxW=$width, maxLines=" . ($maxLines>=0 ? $maxLines : '-') . "): $pass";
	if ($pass === "FAIL") {
		echo "\nExpected:\n[$expected]\nGot:\n[$result]\n";
	} else if ($verbose_test) {
		echo "\nInput:\n[$input]\nResult:\n[$result]\n";
	} else {
		echo "; [$input]";
	}
}
testWrapAndCut("abc defg hijklłmno", 5, -1, "abc\ndefg\nhijklłmno");
testWrapAndCut("abc defg hijklłmno", 10, -1, "abc defg\nhijklłmno");
testWrapAndCut("abc               defg          hijklłmno", 10, -1, "abc defg\nhijklłmno");
testWrapAndCut("abc defg hijklłmno", 2, -1, "abc\ndefg\nhijklłmno");
// long
testWrapAndCut("a23 b67890123456789 c23456789 d2345678 e12 f567 g012 h56789012", 20, -1, "a23 b67890123456789\nc23456789 d2345678\ne12 f567 g012\nh56789012");
// over limit
testWrapAndCut("a23 b67890123456789 c23456789 d2345678 e12 f567 g012 h56789012", 20, 2, "a23 b67890123456789\nc23456789 d2345678");
// move single char to next line
testWrapAndCut("a23 b67890123456789 z d456789012345", 20, 2, "a23 b67890123456789\nz d456789012345");
testWrapAndCut("a23 b6789012345678 z dd456789012345", 20, 2, "a23 b6789012345678\nz dd456789012345");
testWrapAndCut("a23 b678901234567 z ddd456789012345", 20, 2, "a23 b678901234567\nz ddd456789012345");
testWrapAndCut("a23 b67890123456789 z d45678 e12345", 20, 2, "a23 b67890123456789\nz d45678 e12345");
testWrapAndCut("a23 b6789012345678 z dd45678 e12345", 20, 2, "a23 b6789012345678\nz dd45678 e12345");
testWrapAndCut("a23 b678901234567 z ddd45678 e12345", 20, 2, "a23 b678901234567\nz ddd45678 e12345");
// near and over limit, long word
testWrapAndCut("a23 b678901234567 z d4567890123456789", 20, 2, "a23 b678901234567\nz d4567890123456789");
testWrapAndCut("a23 b678901234567 z d45678901234567890", 20, 2, "a23 b678901234567\nz d45678901234567890");
testWrapAndCut("a23 b678901234567 z d456789012345678901", 20, 2, "a23 b678901234567\nz d456789012345678901");
