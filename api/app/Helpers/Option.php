<?php

namespace App\Helpers;

use Illuminate\Database\Eloquent\Model;

/**
 * Simple Option/Maybe monad for nullable results — used in jobs/services.
 *
 * @template T of Model
 */
readonly class Option
{
    private function __construct(
        private bool $isSome,
        private ?Model $value,
    ) {}

    /** @param T $value @return Option<T> */
    public static function some(Model $value): self
    {
        return new self(true, $value);
    }

    /** @return Option<T> */
    public static function none(): self
    {
        return new self(false, null);
    }

    /** @param callable(T):void $fn */
    public function ifSome(callable $fn): void
    {
        if ($this->isSome && $this->value !== null) {
            $fn($this->value);
        }
    }
}
