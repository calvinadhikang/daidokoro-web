<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AssignChannelMenusRequest;
use App\Http\Requests\CopyEventMenusRequest;
use App\Http\Requests\StoreSalesChannelEventRequest;
use App\Http\Requests\UpdateChannelMenuRequest;
use App\Http\Requests\UpdateSalesChannelEventRequest;
use App\Models\MenuModel;
use App\Models\SalesChannel;
use App\Services\MenuCatalogService;
use App\Services\SalesChannelService;
use App\Support\MenuApiFormatter;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class SalesChannelApiController extends Controller
{
    public function __construct(
        private SalesChannelService $salesChannels,
        private MenuCatalogService $menuCatalog,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json([
            'channels' => array_map(
                fn (SalesChannel $channel) => $this->salesChannels->format($channel),
                $this->salesChannels->listForIndex(),
            ),
        ]);
    }

    public function eventsForReport(): JsonResponse
    {
        return response()->json([
            'events' => array_map(
                fn (SalesChannel $channel) => $this->salesChannels->format($channel),
                $this->salesChannels->listAllEventsForReport(),
            ),
        ]);
    }

    public function showEvent(SalesChannel $salesChannel): JsonResponse
    {
        if (! $salesChannel->isEvent()) {
            abort(404);
        }

        return response()->json([
            'channel' => $this->salesChannels->format($salesChannel),
        ]);
    }

    public function current(): JsonResponse
    {
        return response()->json($this->salesChannels->currentPayload());
    }

    public function storeEvent(StoreSalesChannelEventRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $source = null;

        if (! empty($validated['source_event_id'])) {
            $source = SalesChannel::query()->events()->findOrFail($validated['source_event_id']);
        }

        [$channel, $copied] = DB::transaction(function () use ($validated, $source) {
            $channel = $this->salesChannels->createEvent([
                'name' => $validated['name'],
                'starts_at' => $validated['starts_at'],
                'ends_at' => $validated['ends_at'],
            ]);

            $copied = $source === null
                ? 0
                : $this->salesChannels->copyMenusFromEvent($channel, $source);

            return [$channel, $copied];
        });

        return response()->json([
            'success' => true,
            'message' => 'Event created successfully.',
            'copied' => $copied,
            'channel' => $this->salesChannels->format($channel),
        ], 201);
    }

    public function copyMenus(CopyEventMenusRequest $request, SalesChannel $salesChannel): JsonResponse
    {
        abort_unless($salesChannel->isEvent(), 404);

        $source = SalesChannel::query()->events()->findOrFail($request->validated('source_event_id'));
        $copied = $this->salesChannels->copyMenusFromEvent(
            $salesChannel,
            $source,
            $request->validated('menu_ids'),
        );

        $menus = $this->menuCatalog->allForBrowse($salesChannel);

        return response()->json([
            'success' => true,
            'copied' => $copied,
            'menus' => $menus
                ->map(fn (MenuModel $menu) => MenuApiFormatter::formatListItem($menu, $salesChannel))
                ->values()
                ->all(),
        ]);
    }

    public function updateEvent(
        UpdateSalesChannelEventRequest $request,
        SalesChannel $salesChannel,
    ): JsonResponse {
        $channel = $this->salesChannels->updateEvent($salesChannel, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Event updated successfully.',
            'channel' => $this->salesChannels->format($channel),
        ]);
    }

    public function archiveEvent(SalesChannel $salesChannel): JsonResponse
    {
        $this->salesChannels->archiveEvent($salesChannel);

        return response()->json([
            'success' => true,
            'message' => 'Event archived successfully.',
        ]);
    }

    public function unarchiveEvent(SalesChannel $salesChannel): JsonResponse
    {
        $channel = $this->salesChannels->unarchiveEvent($salesChannel);

        return response()->json([
            'success' => true,
            'message' => 'Event restored successfully.',
            'channel' => $this->salesChannels->format($channel),
        ]);
    }

    public function menus(SalesChannel $salesChannel): JsonResponse
    {
        $menus = $this->menuCatalog->allForBrowse($salesChannel);

        return response()->json(
            $menus
                ->map(fn (MenuModel $menu) => MenuApiFormatter::formatListItem($menu, $salesChannel))
                ->values()
        );
    }

    public function assignMenus(
        AssignChannelMenusRequest $request,
        SalesChannel $salesChannel,
    ): JsonResponse {
        $this->salesChannels->assignMenus($salesChannel, $request->validated('menus'));

        $menus = $this->menuCatalog->allForBrowse($salesChannel);

        return response()->json([
            'success' => true,
            'message' => 'Menus assigned successfully.',
            'menus' => $menus
                ->map(fn (MenuModel $menu) => MenuApiFormatter::formatListItem($menu, $salesChannel))
                ->values()
                ->all(),
        ]);
    }

    public function updateMenu(
        UpdateChannelMenuRequest $request,
        SalesChannel $salesChannel,
        MenuModel $menuModel,
    ): JsonResponse {
        $this->salesChannels->updateAssignedMenu(
            $salesChannel,
            $menuModel,
            $request->validated(),
        );

        $menuModel->unsetRelation('salesChannels');
        $menuModel->load([
            'categories:id,name',
            'salesChannels' => fn ($query) => $query->where('sales_channels.id', $salesChannel->id),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Channel menu updated successfully.',
            'menu' => MenuApiFormatter::formatListItem($menuModel, $salesChannel),
        ]);
    }

    public function unassignMenu(SalesChannel $salesChannel, MenuModel $menuModel): JsonResponse
    {
        $this->salesChannels->unassignMenu($salesChannel, $menuModel);

        return response()->json([
            'success' => true,
            'message' => 'Menu removed from this channel.',
        ]);
    }
}
