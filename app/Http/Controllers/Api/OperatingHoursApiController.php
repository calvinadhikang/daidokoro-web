<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOperatingClosureRequest;
use App\Http\Requests\UpdateOperatingHoursRequest;
use App\Models\OperatingClosure;
use App\Models\OperatingHour;
use App\Services\StoreHoursService;
use Illuminate\Http\JsonResponse;

class OperatingHoursApiController extends Controller
{
    public function __construct(private StoreHoursService $storeHours) {}

    public function index(): JsonResponse
    {
        return response()->json($this->payload());
    }

    public function update(UpdateOperatingHoursRequest $request): JsonResponse
    {
        $validated = $request->validated();

        foreach ($validated['days'] as $day) {
            $isClosed = $day['is_closed'] ?? false;

            OperatingHour::query()->updateOrCreate(
                ['day_of_week' => $day['day_of_week']],
                [
                    'is_closed' => $isClosed,
                    'session_1_starts_at' => $isClosed ? null : OperatingHour::formatTimeForStorage($day['session_1_starts_at']),
                    'session_1_ends_at' => $isClosed ? null : OperatingHour::formatTimeForStorage($day['session_1_ends_at']),
                    'session_2_starts_at' => $isClosed ? null : OperatingHour::formatTimeForStorage($day['session_2_starts_at'] ?? null),
                    'session_2_ends_at' => $isClosed ? null : OperatingHour::formatTimeForStorage($day['session_2_ends_at'] ?? null),
                ],
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Operating hours updated successfully.',
            ...$this->payload(),
        ]);
    }

    public function storeClosure(StoreOperatingClosureRequest $request): JsonResponse
    {
        $validated = $request->validated();

        OperatingClosure::query()->create([
            'starts_at' => $validated['starts_at'],
            'ends_at' => $validated['ends_at'],
            'label' => $validated['label'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Closed period added successfully.',
            ...$this->payload(),
        ], 201);
    }

    public function destroyClosure(OperatingClosure $closure): JsonResponse
    {
        $closure->delete();

        return response()->json([
            'success' => true,
            'message' => 'Closed period removed successfully.',
            ...$this->payload(),
        ]);
    }

    /**
     * @return array{
     *     days: list<array<string, mixed>>,
     *     closures: list<array<string, mixed>>,
     *     storeStatus: array<string, mixed>,
     *     today: string
     * }
     */
    private function payload(): array
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
            ])
            ->values()
            ->all();

        $closures = OperatingClosure::query()
            ->endingOnOrAfterToday()
            ->orderBy('starts_at')
            ->get()
            ->map(fn (OperatingClosure $closure) => [
                'id' => $closure->id,
                'starts_at' => $closure->starts_at->toDateString(),
                'ends_at' => $closure->ends_at->toDateString(),
                'label' => $closure->label,
            ])
            ->values()
            ->all();

        return [
            'days' => $days,
            'closures' => $closures,
            'storeStatus' => $this->storeHours->status(),
            'today' => $this->storeHours->today(),
        ];
    }
}
