<?php

namespace nochso\Phormat;

/**
 * Based on PR https://github.com/nikic/PHP-Parser/pull/116 by https://github.com/BluntSporks
 */
class LineWrapper
{
	const LIMIT = 120;
	const TOKENS = [
//		',' => true,
		'.' => true,
		T_BOOLEAN_AND => true,
		T_BOOLEAN_OR => true,
		T_LOGICAL_AND => true,
		T_LOGICAL_OR => true,
		T_LOGICAL_XOR => true,
		T_OBJECT_OPERATOR => true,
	];

	public function wrap($code)
	{
		$lines = $this->getLines($code);
		$output = '';
		foreach ($lines as $line) {
			$output = $this->wrapLine($line, $output);
//          foreach ($line as $part) {
//              list($frag, $wrappable) = $part;
//              $fragLen = $this->lineLen($frag);
////              if ($prevWrappable
//			  	if (($wrappable || $prevWrappable)
////                      && $fragLen > 1
//                      && $lineLen + $fragLen >= self::LIMIT) {
//                  $wrapFrag = ltrim($frag);
//                  $wrapFragLen = $this->lineLen($wrapFrag);
//                  $start = $indent . "\t";
//                  $lineLen = $this->lineLen($start) + $wrapFragLen;
//                  $output .= "\n" . $start . $wrapFrag;
//              } else {
//                  $lineLen += $fragLen;
//                  $output .= $frag;
//              }
//              $prevWrappable = $wrappable;
//          }
		}

		return $output;
	}

	/**
	 * Get the length of a line, including expansion of tabs.
	 *
	 * @param string $line
	 *
	 * @return int
	 */
	protected function lineLen($line)
	{
		// Subtract 1 from $tabStop because the tab itself is already included in the length.
		return strlen($line) + substr_count($line, "\t") * (4 - 1);
	}

	private function getLines($code)
	{
		$wrapTokens = self::TOKENS;
		$tokens = token_get_all($code);
		// Split into lines and identify wrappable tokens
		$lines = array();
		$line = array();
		$part = array();
		foreach ($tokens as $i => $token) {
			if (is_array($token)) {
				list($type, $text) = $token;
			} else {
				$type = $text = $token;
			}
			$frags = preg_split("/(\n)/", $text, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
			foreach ($frags as $frag) {
				if ($frag == "\n") {
					// Newlines end a line, so add any existing part then reset line and part.
					if ($part) {
						$line[] = $part;
						$part = array();
					}
					$line[] = array(
						"\n",
						false,
					);
					$lines[] = $line;
					$line = array();
				} elseif (isset($wrapTokens[$type])) {
					// Wrappable parts are stored separately within a line, so add any existing part then add wrap.
					if ($part) {
						$line[] = $part;
						$part = array();
					}
					$line[] = array(
						$frag,
						true,
					);
				} else {
					// Non-wrappable parts accumulate until they are added to a line
					if ($part) {
						$part[0] .= $frag;
					} else {
						$part = array(
							$frag,
							false,
						);
					}
				}
			}
		}
		// Add any leftovers.
		if ($part) {
			$line[] = $part;
		}
		if ($line) {
			$lines[] = $line;
			return array($lines, $line, $part, $frag);
		}
		return $lines;
	}

	private function wrapLine($line, $output)
	{
		preg_match('/^[ \t]*/', $line[0][0], $match);
		$indent = $match[0];
		$lineLen = 0;
		$wrapToken = $this->detectWrapToken($line);
		foreach ($line as $key => $part) {
			list($frag, $wrappable) = $part;
			$wrappable = $frag === $wrapToken;
			$fragLen = $this->lineLen($frag);
			$nextFragLen = 0;
			if (isset($line[$key + 1])) {
				$nextFragLen = $this->lineLen($line[$key + 1][0]);
			}
//			if ($wrappable && ($lineLen + $fragLen >= self::LIMIT || $lineLen + $fragLen + $nextFragLen >= self::LIMIT)) {
			if ($wrappable) {
				$wrapFrag = ltrim($frag);
				$wrapFragLen = $this->lineLen($wrapFrag);
				$start = $indent . "\t";
				$lineLen = $this->lineLen($start) + $wrapFragLen;
				$output = rtrim($output, ' ');
				$output .= "\n" . $start . $wrapFrag;
			} else {
				$lineLen += $fragLen;
				$output .= $frag;
			}
		}
		return $output;
	}

	private function detectWrapToken($line)
	{
		$wrapTokenCount = [];
		$out = '';
		foreach ($line as $part) {
			list($frag, $wrappable) = $part;
			if ($wrappable) {
				if (!isset($wrapTokenCount[$frag])) {
					$wrapTokenCount[$frag] = 0;
				}
				$wrapTokenCount[$frag]++;
			}
			$out .= $frag;
		}
		if ($this->lineLen($out) > self::LIMIT) {
//			if (isset($wrapTokenCount['.'])) {
//				return '.';
//			}
			arsort($wrapTokenCount);
			reset($wrapTokenCount);
			return key($wrapTokenCount);
		}
		return null;
	}
}
