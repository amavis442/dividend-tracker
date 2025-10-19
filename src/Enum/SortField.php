<?php

namespace App\Enum;

enum SortField: string
{
	case SYMBOL = 'symbol';
	case DIVIDEND = 'dividend';
	case YIELD = 'yield';

	public static function fromString(string $value): self
	{
		return match (strtolower($value)) {
			'symbol' => self::SYMBOL,
			'dividend' => self::DIVIDEND,
			'yield' => self::YIELD,
			default => self::SYMBOL, // fallback if input is invalid
		};
	}

	public function toString(): string
	{
		return match ($this) {
			self::SYMBOL => 'symbol',
			self::DIVIDEND => 'dividend',
			self::YIELD => 'yield',
		};
	}


	public function toLabel(): string
	{
		return match ($this) {
			self::SYMBOL => 'Ticker Symbol',
			self::DIVIDEND => 'Annual Dividend',
			self::YIELD => 'Dividend Yield',
		};
	}
}
