<?php

namespace App\Enum;

enum SortDirection: string
{
	case ASC = 'asc';
	case DESC = 'desc';

	public static function fromString(string $value): self
	{
		return match (strtolower($value)) {
			'asc' => self::ASC,
			'desc' => self::DESC,
			default => self::ASC, // fallback if input is invalid
		};
	}

	public function toString(): string
	{
		return match ($this) {
			self::ASC => 'asc',
			self::DESC => 'desc',
		};
	}

	public function toLabel(): string
	{
		return match ($this) {
			self::ASC => 'Ascending',
			self::DESC => 'Descending',
		};
	}
}
