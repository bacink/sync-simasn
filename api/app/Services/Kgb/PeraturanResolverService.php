<?php

namespace App\Services\Kgb;

use App\Models\RefPeraturan;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Resolves the active regulation for salary calculations.
 *
 * Regulations are versioned by `effective_date` — the active regulation is the
 * one with the latest `effective_date` that is not in the future.
 */
class PeraturanResolverService
{
    /**
     * Return the regulation that should apply at a given point in time.
     *
     * @param  \DateTimeInterface|string|null  $asOf  Defaults to now.
     * @throws ModelNotFoundException           when no regulation exists.
     */
    public function getActive(\DateTimeInterface|string $asOf = null): RefPeraturan
    {
        $asOf ??= now();

        return RefPeraturan::query()
            ->where('effective_date', '<=', $asOf)
            ->orderByDesc('effective_date')
            ->firstOrFail();
    }

    /**
     * Return the regulation that was active at a specific effective date.
     * Useful for historical lookups.
     *
     * @throws ModelNotFoundException when no regulation matches.
     */
    public function getAtEffectiveDate(\DateTimeInterface|string $date): RefPeraturan
    {
        return RefPeraturan::query()
            ->where('effective_date', '<=', $date)
            ->orderByDesc('effective_date')
            ->firstOrFail();
    }

    /**
     * Return all regulations ordered newest-first.
     */
    public function getAllOrdered(): \Illuminate\Database\Eloquent\Collection
    {
        return RefPeraturan::query()
            ->orderByDesc('effective_date')
            ->orderByDesc('id')
            ->get();
    }
}