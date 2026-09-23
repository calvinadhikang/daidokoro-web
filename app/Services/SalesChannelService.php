<?php

namespace App\Services;

use App\Models\MenuModel;
use App\Models\OperatingClosure;
use App\Models\SalesChannel;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class SalesChannelService
{
    public function __construct(private StoreHoursService $storeHours) {}

    public function store(): SalesChannel
    {
        return SalesChannel::store();
    }

    public function resolve(?int $id): SalesChannel
    {
        if ($id === null) {
            return $this->store();
        }

        $channel = SalesChannel::query()->active()->find($id);

        if ($channel === null) {
            throw ValidationException::withMessages([
                'sales_channel_id' => 'The selected sales channel is invalid.',
            ]);
        }

        return $channel;
    }

    public function resolveEvent(int $id): SalesChannel
    {
        $channel = SalesChannel::query()->events()->find($id);

        if ($channel === null) {
            throw ValidationException::withMessages([
                'event_id' => 'The selected event is invalid.',
            ]);
        }

        return $channel;
    }

    /**
     * @return list<SalesChannel>
     */
    public function listAllEventsForReport(): array
    {
        return SalesChannel::query()
            ->events()
            ->orderByDesc('starts_at')
            ->orderByDesc('id')
            ->get()
            ->all();
    }

    public function eventCovering(?string $date = null): ?SalesChannel
    {
        $date ??= $this->storeHours->today();

        return SalesChannel::query()
            ->active()
            ->events()
            ->whereDate('starts_at', '<=', $date)
            ->whereDate('ends_at', '>=', $date)
            ->orderBy('starts_at')
            ->first();
    }

    public function suggestedChannel(?string $date = null): SalesChannel
    {
        return $this->eventCovering($date) ?? $this->store();
    }

    /**
     * @return list<SalesChannel>
     */
    public function listForIndex(): array
    {
        return SalesChannel::query()
            ->active()
            ->orderByRaw("type = ? desc", [SalesChannel::TYPE_STORE])
            ->orderBy('starts_at')
            ->orderBy('name')
            ->get()
            ->all();
    }

    public function menusOverlap(string $startsAt, string $endsAt, ?int $exceptId = null): bool
    {
        return $this->eventsOverlap($startsAt, $endsAt, $exceptId);
    }

    public function eventsOverlap(string $startsAt, string $endsAt, ?int $exceptId = null): bool
    {
        return SalesChannel::query()
            ->active()
            ->events()
            ->when($exceptId !== null, fn ($query) => $query->where('id', '!=', $exceptId))
            ->whereDate('starts_at', '<=', $endsAt)
            ->whereDate('ends_at', '>=', $startsAt)
            ->exists();
    }

    /**
     * @param  array{name: string, starts_at: string, ends_at: string}  $data
     */
    public function createEvent(array $data): SalesChannel
    {
        $this->assertEventDates($data['starts_at'], $data['ends_at']);

        $channel = SalesChannel::query()->create([
            'type' => SalesChannel::TYPE_EVENT,
            'name' => $data['name'],
            'starts_at' => $data['starts_at'],
            'ends_at' => $data['ends_at'],
            'closes_store' => true,
        ]);

        $this->syncLinkedClosure($channel);

        return $channel->fresh(['closure']) ?? $channel;
    }

    /**
     * @param  array{name?: string, starts_at?: string, ends_at?: string}  $data
     */
    public function updateEvent(SalesChannel $channel, array $data): SalesChannel
    {
        $this->assertEvent($channel);

        $startsAt = $data['starts_at'] ?? $channel->starts_at?->toDateString();
        $endsAt = $data['ends_at'] ?? $channel->ends_at?->toDateString();

        if ($startsAt === null || $endsAt === null) {
            throw ValidationException::withMessages([
                'starts_at' => 'Event dates are required.',
            ]);
        }

        $this->assertEventDates($startsAt, $endsAt, $channel->id);

        $channel->update([
            'name' => $data['name'] ?? $channel->name,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
        ]);

        $this->syncLinkedClosure($channel->fresh() ?? $channel);

        return $channel->fresh(['closure']) ?? $channel;
    }

    public function archiveEvent(SalesChannel $channel): void
    {
        $this->assertEvent($channel);

        $today = $this->storeHours->today();
        $hasEnded = $channel->ends_at !== null && $channel->ends_at->toDateString() < $today;
        $hasTransactions = $channel->transactions()->exists();

        if ($hasTransactions && ! $hasEnded) {
            throw ValidationException::withMessages([
                'channel' => 'Events with transactions can only be archived after they end.',
            ]);
        }

        $channel->update(['archived_at' => Carbon::now(StoreHoursService::TIMEZONE)]);

        if ($channel->ends_at === null || $channel->ends_at->toDateString() >= $today) {
            $channel->closure()->delete();
        }
    }

    public function unarchiveEvent(SalesChannel $channel): SalesChannel
    {
        $this->assertEvent($channel);

        if (! $channel->isArchived()) {
            return $channel;
        }

        $channel->update(['archived_at' => null]);

        $channel = $channel->fresh(['closure']) ?? $channel;
        $this->syncLinkedClosure($channel);

        return $channel->fresh(['closure']) ?? $channel;
    }

    /**
     * @param  list<array{menu_id: int, price_override?: int|null}>  $menus
     */
    public function assignMenus(SalesChannel $channel, array $menus): SalesChannel
    {
        $payload = [];

        foreach ($menus as $row) {
            $payload[$row['menu_id']] = [
                'price_override' => $row['price_override'] ?? null,
                'is_available' => true,
            ];
        }

        $channel->menus()->syncWithoutDetaching($payload);

        return $channel;
    }

    /**
     * @param  list<int>  $channelIds
     */
    public function syncMenuChannels(MenuModel $menu, array $channelIds): void
    {
        $channelIds = array_values(array_unique(array_map('intval', $channelIds)));

        if ($channelIds === []) {
            $channelIds = [$this->store()->id];
        }

        $existing = $menu->salesChannels()->get()->keyBy('id');
        $sync = [];

        foreach ($channelIds as $channelId) {
            $pivot = $existing->get($channelId)?->pivot;
            $sync[$channelId] = [
                'price_override' => $pivot?->price_override,
                'is_available' => $pivot?->is_available ?? true,
            ];
        }

        $menu->salesChannels()->sync($sync);
    }

    public function updateAssignedMenu(
        SalesChannel $channel,
        MenuModel $menu,
        array $data,
    ): void {
        $this->assertAssigned($menu, $channel, requireAvailable: false);

        $payload = [];

        if (array_key_exists('price_override', $data)) {
            $payload['price_override'] = $data['price_override'];
        }

        if (array_key_exists('is_available', $data)) {
            $payload['is_available'] = $data['is_available'];
        }

        if ($payload !== []) {
            $channel->menus()->updateExistingPivot($menu->id, $payload);
        }
    }

    public function unassignMenu(SalesChannel $channel, MenuModel $menu): void
    {
        $this->assertAssigned($menu, $channel, requireAvailable: false);

        if ($channel->isStore()) {
            $otherChannels = $menu->salesChannels()
                ->where('sales_channels.id', '!=', $channel->id)
                ->exists();

            if (! $otherChannels) {
                throw ValidationException::withMessages([
                    'menu' => 'A store-only menu cannot be removed from the store catalog. Delete the menu instead.',
                ]);
            }
        }

        $channel->menus()->detach($menu->id);
    }

    public function toggleAvailability(SalesChannel $channel, MenuModel $menu): MenuModel
    {
        $this->assertAssigned($menu, $channel, requireAvailable: false);

        $current = (bool) $menu->channelPivot($channel)?->is_available;
        $channel->menus()->updateExistingPivot($menu->id, [
            'is_available' => ! $current,
        ]);

        $menu->unsetRelation('salesChannels');
        $menu->load(['salesChannels' => fn ($query) => $query->where('sales_channels.id', $channel->id)]);

        return $menu;
    }

    public function assertAssigned(
        MenuModel $menu,
        SalesChannel $channel,
        bool $requireAvailable = true,
    ): void {
        $assigned = $menu->salesChannels()
            ->where('sales_channels.id', $channel->id)
            ->first();

        if ($assigned === null) {
            throw ValidationException::withMessages([
                'items' => "{$menu->name} is not available on this sales channel.",
            ]);
        }

        if ($requireAvailable && (! $menu->is_available || ! $assigned->pivot->is_available)) {
            throw ValidationException::withMessages([
                'items' => "{$menu->name} is no longer available.",
            ]);
        }
    }

    public function syncLinkedClosure(SalesChannel $channel): void
    {
        if (! $channel->isEvent() || ! $channel->closes_store) {
            return;
        }

        $startsAt = $channel->starts_at?->toDateString();
        $endsAt = $channel->ends_at?->toDateString();

        if ($startsAt === null || $endsAt === null) {
            return;
        }

        $existing = $channel->closure;

        if ($existing !== null) {
            $existing->update([
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'label' => $channel->name,
            ]);

            return;
        }

        if (OperatingClosure::overlaps($startsAt, $endsAt)) {
            return;
        }

        OperatingClosure::query()->create([
            'sales_channel_id' => $channel->id,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'label' => $channel->name,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function format(SalesChannel $channel): array
    {
        $today = $this->storeHours->today();

        $status = 'store';

        if ($channel->isEvent()) {
            if ($channel->starts_at !== null && $channel->starts_at->toDateString() > $today) {
                $status = 'upcoming';
            } elseif ($channel->coversDate($today)) {
                $status = 'active';
            } else {
                $status = 'ended';
            }
        }

        return [
            'id' => $channel->id,
            'type' => $channel->type,
            'name' => $channel->name,
            'starts_at' => $channel->starts_at?->toDateString(),
            'ends_at' => $channel->ends_at?->toDateString(),
            'closes_store' => $channel->closes_store,
            'status' => $status,
            'is_store' => $channel->isStore(),
            'is_archived' => $channel->isArchived(),
        ];
    }

    /**
     * @return array{
     *     today: string,
     *     suggested: array<string, mixed>,
     *     store: array<string, mixed>,
     *     active_event: array<string, mixed>|null,
     *     channels: list<array<string, mixed>>
     * }
     */
    public function currentPayload(): array
    {
        $store = $this->store();
        $activeEvent = $this->eventCovering();
        $suggested = $activeEvent ?? $store;

        return [
            'today' => $this->storeHours->today(),
            'suggested' => $this->format($suggested),
            'store' => $this->format($store),
            'active_event' => $activeEvent === null ? null : $this->format($activeEvent),
            'channels' => array_map(
                fn (SalesChannel $channel) => $this->format($channel),
                $this->listForIndex(),
            ),
        ];
    }

    private function assertEvent(SalesChannel $channel): void
    {
        if (! $channel->isEvent()) {
            throw ValidationException::withMessages([
                'channel' => 'The store channel cannot be modified this way.',
            ]);
        }
    }

    private function assertEventDates(string $startsAt, string $endsAt, ?int $exceptId = null): void
    {
        $today = $this->storeHours->today();

        if ($endsAt < $startsAt) {
            throw ValidationException::withMessages([
                'ends_at' => 'The end date must be on or after the start date.',
            ]);
        }

        if ($exceptId === null && $startsAt < $today) {
            throw ValidationException::withMessages([
                'starts_at' => 'Event dates cannot start in the past.',
            ]);
        }

        if ($this->eventsOverlap($startsAt, $endsAt, $exceptId)) {
            throw ValidationException::withMessages([
                'starts_at' => 'This date range overlaps with another event.',
            ]);
        }
    }
}
