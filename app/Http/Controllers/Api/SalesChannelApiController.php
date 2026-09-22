<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AssignChannelMenusRequest;
use App\Http\Requests\StoreSalesChannelEventRequest;
use App\Http\Requests\UpdateChannelMenuRequest;
use App\Http\Requests\UpdateSalesChannelEventRequest;
use App\Models\MenuModel;
use App\Models\SalesChannel;
use App\Services\MenuCatalogService;
use App\Services\SalesChannelService;
use App\Support\MenuApiFormatter;
use Illuminate\Http\JsonResponse;

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

    public function current(): JsonResponse
    {
        return response()->json($this->salesChannels->currentPayload());
    }

    public function storeEvent(StoreSalesChannelEventRequest $request): JsonResponse
    {
        $channel = $this->salesChannels->createEvent($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Event created successfully.',
            'channel' => $this->salesChannels->format($channel),
        ], 201);
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
