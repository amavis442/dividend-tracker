<?php
namespace App\Factory\Provider;

use Faker\Provider\Base;

final class ISINProvider extends Base
{
	public function isin()
	{
        $countryCode = strtoupper($this->lexify('??')); // Two uppercase letters
		$identifier = $this->numerify('############'); // 10 digits
		$base = $countryCode . $identifier;

		// Convert letters to numbers: A=10, B=11... Z=35
		$converted = '';
		foreach (str_split($base) as $char) {
			if (ctype_alpha($char)) {
				$converted .= ord($char) - 55;
			} else {
				$converted .= $char;
			}
		}

		// Luhn algorithm
		$sum = 0;
		$alt = false;
		for ($i = strlen($converted) - 1; $i >= 0; $i--) {
			$n = intval($converted[$i]);
			if ($alt) {
				$n *= 2;
				if ($n > 9) {
					$n -= 9;
				}
			}
			$sum += $n;
			$alt = !$alt;
		}

		$checkDigit = (10 - ($sum % 10)) % 10;

		return $base . $checkDigit;
	}
}
