<?php function getArrayKeyFromField(string $needle, array $haystack, string $keyField)
{
	$out = \array_reduce($haystack, function ($carry, $item) use ($keyField, $needle) {
		if ($item[$keyField] == $needle) {
			$carry = $item;
		}
		return $carry;
	});

	return $out;
}

$out = getArrayKeyFromField('hello', [['short' => 'hi'], ['short' => 'hello'], ['short' => 'bye']], 'short');

var_dump($out);
