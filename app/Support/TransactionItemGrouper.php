<?php

namespace App\Support;

use App\Models\TransactionItem;
use Illuminate\Support\Collection;

class TransactionItemGrouper
{
    /**
     * Group items by the minute they were ordered.
     *
     * @param  Collection<int, TransactionItem>|list<TransactionItem>  $items
     * @return list<array{
     *     ordered_at: string,
     *     items: list<array<string, mixed>>
     * }>
     */
    public static function groupByOrderedAt(Collection|array $items): array
    {
        $collection = $items instanceof Collection ? $items : collect($items);

        return $collection
            ->sortBy('created_at')
            ->groupBy(fn (TransactionItem $item) => $item->created_at?->format('Y-m-d H:i') ?? '')
            ->map(function (Collection $group, string $orderedAtKey) {
                /** @var TransactionItem $first */
                $first = $group->first();

                return [
                    'ordered_at' => $first->created_at?->toIso8601String() ?? $orderedAtKey,
                    'items' => $group->values()->all(),
                ];
            })
            ->values()
            ->all();
    }
}
