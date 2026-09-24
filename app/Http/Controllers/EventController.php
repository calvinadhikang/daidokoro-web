<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssignChannelMenusRequest;
use App\Http\Requests\StoreSalesChannelEventRequest;
use App\Http\Requests\UpdateChannelMenuRequest;
use App\Http\Requests\UpdateSalesChannelEventRequest;
use App\Models\MenuModel;
use App\Models\SalesChannel;
use App\Services\MenuCatalogService;
use App\Services\SalesChannelService;
use App\Support\MenuApiFormatter;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class EventController extends Controller
{
    public function __construct(
        private SalesChannelService $salesChannels,
        private MenuCatalogService $menuCatalog,
    ) {}

    public function index(): Response
    {
        return Inertia::render('admin/events/index', [
            'channels' => array_map(
                fn (SalesChannel $channel) => $this->salesChannels->format($channel),
                $this->salesChannels->listForIndex(),
            ),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/events/create');
    }

    public function store(StoreSalesChannelEventRequest $request): RedirectResponse
    {
        $channel = $this->salesChannels->createEvent($request->validated());

        return redirect()
            ->route('admin.events.show', $channel)
            ->with('success', 'Event created successfully.');
    }

    public function show(SalesChannel $salesChannel): Response
    {
        abort_unless($salesChannel->isEvent() && ! $salesChannel->isArchived(), 404);

        $assigned = $this->menuCatalog->allForBrowse($salesChannel);
        $store = $this->salesChannels->store();
        $storeMenus = $this->menuCatalog->allForBrowse($store);

        return Inertia::render('admin/events/show', [
            'channel' => $this->salesChannels->format($salesChannel),
            'assignedMenus' => $assigned
                ->map(fn (MenuModel $menu) => MenuApiFormatter::formatListItem($menu, $salesChannel))
                ->values()
                ->all(),
            'storeMenus' => $storeMenus
                ->map(fn (MenuModel $menu) => MenuApiFormatter::formatListItem($menu, $store))
                ->values()
                ->all(),
        ]);
    }

    public function update(UpdateSalesChannelEventRequest $request, SalesChannel $salesChannel): RedirectResponse
    {
        $this->salesChannels->updateEvent($salesChannel, $request->validated());

        return redirect()
            ->route('admin.events.show', $salesChannel)
            ->with('success', 'Event updated successfully.');
    }

    public function archive(SalesChannel $salesChannel): RedirectResponse
    {
        $this->salesChannels->archiveEvent($salesChannel);

        return redirect()
            ->route('admin.events.index')
            ->with('success', 'Event archived successfully.');
    }

    public function assignMenus(AssignChannelMenusRequest $request, SalesChannel $salesChannel): RedirectResponse
    {
        $this->salesChannels->assignMenus($salesChannel, $request->validated('menus'));

        return redirect()
            ->route('admin.events.show', $salesChannel)
            ->with('success', 'Menus assigned successfully.');
    }

    public function updateMenu(
        UpdateChannelMenuRequest $request,
        SalesChannel $salesChannel,
        MenuModel $menuModel,
    ): RedirectResponse {
        $this->salesChannels->updateAssignedMenu($salesChannel, $menuModel, $request->validated());

        return redirect()
            ->route('admin.events.show', $salesChannel)
            ->with('success', 'Channel menu updated.');
    }

    public function unassignMenu(SalesChannel $salesChannel, MenuModel $menuModel): RedirectResponse
    {
        $this->salesChannels->unassignMenu($salesChannel, $menuModel);

        return redirect()
            ->route('admin.events.show', $salesChannel)
            ->with('success', 'Menu removed from this event.');
    }
}
