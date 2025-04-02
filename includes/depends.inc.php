<?php
/**
 * Helpers for depends graphs.
 */

/**
 * Wrap a string at word boundaries (space/tab only) and cut to max lines.
 *
 * @param string $text The input text.
 * @param int $wrapWidth The max width per line.
 * @param int $maxLines The max number of lines to return.
 * @return string The wrapped and trimmed string.
 */
function wrapAndCut(string $text, int $wrapWidth, int $maxLines = -1, string $appendOnMax = ""): string {
	// cleanup text
	$textline = preg_replace("#  +#", " ", strtr(trim($text), [
		"\t" => " ",
		"\r\n" => " ",
		"\r" => " ",
		"\n" => " ",
	]));
	
	$byteLength = strlen($textline);
	$result = '';
	$lastCutIndex = 0;
	$lastSpaceIndex = 0;
	$finalCut = false;
	for (
		$spaceIndex = strpos($textline, ' '), $linesInResult = 0;
		$spaceIndex !== false || $lastSpaceIndex > 0;
		$spaceIndex = strpos($textline, ' ', $spaceIndex + 3)
	) {
		if ($spaceIndex === false) {
			$spaceIndex = $byteLength;
			$finalCut = true;
		}
		$curLineLenght = $spaceIndex - $lastCutIndex;
		if ($curLineLenght > $wrapWidth || $finalCut) {
			if ($curLineLenght > $wrapWidth) {
				$lineLenght = $lastSpaceIndex - $lastCutIndex;
				if ($lineLenght <= 1) {
					$lineLenght = $curLineLenght;
					$lastSpaceIndex = $spaceIndex;
				}
			} else {
				$lineLenght = $curLineLenght;
			}
			$line = substr($textline, $lastCutIndex, $lineLenght);
			$result .= $line;
			$lastCutIndex = $lastSpaceIndex + 1;
			$linesInResult++;
			if ($finalCut) {
				if ($lineLenght != $curLineLenght) {
					$line = substr($textline, $lastCutIndex);
					$result .= "\n";
					$result .= $line;
				}
				break;
			}
			if ($maxLines > 0 && $linesInResult >= $maxLines) {
				if (!empty($appendOnMax)) {
					$result .= $appendOnMax;
				}
				break;
			}
			$result .= "\n";
		}
		$lastSpaceIndex = $spaceIndex;
	}

	return $result;
}