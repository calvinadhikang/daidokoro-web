<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOperatingClosureRequest;
use App\Http\Requests\UpdateOperatingHoursRequest;
use App\Models\OperatingClosure;
use App\Models\OperatingHour;
use App\Services\StoreHoursService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class OperationalHoursController extends Controller
{
    public function __construct(private StoreHoursService $storeHours) {}

    public function edit(): Response
    {
        OperatingHour::ensureWeekExists();

        $days = OperatingHour::query()
            ->orderBy('day_of_week')
            ->get()
            ->map(fn (OperatingHour $day) => [
                'day_of_week' => $day->day_of_week,
                'day_name' => OperatingHour::DAY_NAMES[$day->day_of_week],
                'is_closed' => $day->is_closed,
                'session_1_starts_at' => OperatingHour::formatTimeForInput($day->session_1_starts_at),
                'session_1_ends_at' => OperatingHour::formatTimeForInput($day->session_1_ends_at),
                'session_2_starts_at' => OperatingHour::formatTimeForInput($day->session_2_starts_at),
                'session_2_ends_at' => OperatingHour::formatTimeForInput($day->session_2_ends_at),
            ]);

        $closures = OperatingClosure::query()
            ->endingOnOrAfterToday()
            ->orderBy('starts_at')
            ->get()
            ->map(fn (OperatingClosure $closure) => [
                'id' => $closure->id,
                'starts_at' => $closure->starts_at->toDateString(),
                'ends_at' => $closure->ends_at->toDateString(),
                'label' => $closure->label,
            ]);

        return Inertia::render('admin/hours/index', [
            'days' => $days,
            'closures' => $closures,
            'storeStatus' => $this->storeHours->status(),
            'today' => $this->storeHours->today(),
        ]);
    }

    public function update(UpdateOperatingHoursRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        foreach ($validated['days'] as $day) {
            $isClosed = $day['is_closed'] ?? false;

            OperatingHour::query()->updateOrCreate(
                ['day_of_week' => $day['day_of_week']],
                [
                    'is_closed' => $isClosed,
                    'session_1_starts_at' => $isClosed ? null : $this->formatTimeForStorage($day['session_1_starts_at']),
                    'session_1_ends_at' => $isClosed ? null : $this->formatTimeForStorage($day['session_1_ends_at']),
                    'session_2_starts_at' => $isClosed ? null : $this->formatTimeForStorage($day['session_2_starts_at'] ?? null),
                    'session_2_ends_at' => $isClosed ? null : $this->formatTimeForStorage($day['session_2_ends_at'] ?? null),
                ],
            );
        }

        return redirect()
            ->route('admin.hours.edit')
            ->with('success', 'Operating hours updated successfully.');
    }

    public function storeClosure(StoreOperatingClosureRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        OperatingClosure::query()->create([
            'starts_at' => $validated['starts_at'],
            'ends_at' => $validated['ends_at'],
            'label' => $validated['label'] ?? null,
        ]);

        return redirect()
            ->route('admin.hours.edit')
            ->with('success', 'Closed period added successfully.');
    }

    public function destroyClosure(OperatingClosure $closure): RedirectResponse
    {
        $closure->delete();

        return redirect()
            ->route('admin.hours.edit')
            ->with('success', 'Closed period removed successfully.');
    }

    private function formatTimeForStorage(?string $time): ?string
    {
        if ($time === null || $time === '') {
            return null;
        }

        return strlen($time) === 5 ? "{$time}:00" : $time;
    }
}
