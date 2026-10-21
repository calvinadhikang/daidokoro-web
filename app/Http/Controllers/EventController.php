<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ManagesMenuImages;
use App\Http\Requests\AssignChannelMenusRequest;
use App\Http\Requests\CopyEventMenusRequest;
use App\Http\Requests\StoreMenuRequest;
use App\Http\Requests\StoreSalesChannelEventRequest;
use App\Http\Requests\UpdateChannelMenuRequest;
use App\Http\Requests\UpdateSalesChannelEventRequest;
use App\Models\MenuAddonGroup;
use App\Models\MenuAddonOption;
use App\Models\MenuModel;
use App\Models\SalesChannel;
use App\Services\MenuCatalogService;
use App\Services\MenuImageService;
use App\Services\SalesChannelService;
use App\Support\MenuApiFormatter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class EventController extends Controller
{
    use ManagesMenuImages;

    public function __construct(
        private SalesChannelService $salesChannels,
        private MenuCatalogService $menuCatalog,
        private MenuImageService $menuImages,
    ) {}

    public function index(): Response
    {
        return Inertia::render('admin/events/index', [
            'channels' => array_map(
                fn (SalesChannel $channel) => $this->salesChannels->format($channel),
                $this->salesChannels->listAllEventsForReport(),
            ),
        ]);
    }

    public function create(Request $request): Response
    {
        $from = $request->integer('from');
        $source = $from > 0 ? SalesChannel::query()->events()->find($from) : null;

        return Inertia::render('admin/events/create', [
            'events' => $this->salesChannels->eventCopySources(),
            'sourceEventId' => $source?->id,
            'defaults' => [
                'name' => $source?->name ?? '',
            ],
        ]);
    }

    public function store(StoreSalesChannelEventRequest $request): RedirectResponse
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

        return redirect()
            ->route('admin.events.show', $channel)
            ->with('success', $this->createdMessage($source, $copied));
    }

    public function show(SalesChannel $salesChannel): Response
    {
        abort_unless($salesChannel->isEvent(), 404);

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
            'copySources' => $this->salesChannels->eventCopySources($salesChannel->id, withMenus: true),
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

    public function unarchive(SalesChannel $salesChannel): RedirectResponse
    {
        $this->salesChannels->unarchiveEvent($salesChannel);

        return redirect()
            ->route('admin.events.show', $salesChannel)
            ->with('success', 'Event restored successfully.');
    }

    public function createMenu(SalesChannel $salesChannel): Response
    {
        abort_unless($salesChannel->isEvent(), 404);

        return Inertia::render('admin/events/menu-create', [
            'channel' => $this->salesChannels->format($salesChannel),
        ]);
    }

    public function storeMenu(StoreMenuRequest $request, SalesChannel $salesChannel): RedirectResponse
    {
        abort_unless($salesChannel->isEvent(), 404);

        $validated = $request->validated();

        DB::transaction(function () use ($validated, $request, $salesChannel) {
            $menu = new MenuModel([
                'name' => $validated['name'],
                'image' => $this->resolveMenuImageUrl($request, $this->menuImages),
                'price' => $validated['price'],
                'pricing_type' => $validated['pricing_type'] ?? MenuModel::PRICING_STANDARD,
                'is_available' => $validated['is_available'] ?? true,
                'is_recommended' => $validated['is_recommended'] ?? false,
            ]);
            $menu->assignToSalesChannelId = $salesChannel->id;
            $menu->save();

            $this->syncAddonGroups($menu, $validated['addon_groups'] ?? []);
            $menu->categories()->sync($validated['category_ids'] ?? []);
        });

        return redirect()
            ->route('admin.events.show', $salesChannel)
            ->with('success', 'Event menu created successfully.');
    }

    public function assignMenus(AssignChannelMenusRequest $request, SalesChannel $salesChannel): RedirectResponse
    {
        $this->salesChannels->assignMenus($salesChannel, $request->validated('menus'));

        return redirect()
            ->route('admin.events.show', $salesChannel)
            ->with('success', 'Menus assigned successfully.');
    }

    public function copyMenus(CopyEventMenusRequest $request, SalesChannel $salesChannel): RedirectResponse
    {
        abort_unless($salesChannel->isEvent(), 404);

        $source = SalesChannel::query()->events()->findOrFail($request->validated('source_event_id'));
        $menuIds = $request->validated('menu_ids');
        $copied = $this->salesChannels->copyMenusFromEvent($salesChannel, $source, $menuIds);

        return redirect()
            ->route('admin.events.show', $salesChannel)
            ->with('success', $this->copiedMessage($source, $copied));
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

    private function createdMessage(?SalesChannel $source, int $copied): string
    {
        if ($source === null) {
            return 'Event created successfully.';
        }

        if ($copied === 0) {
            return "Event created. {$source->name} has no menus to copy.";
        }

        if ($copied === 1) {
            return "Event created with 1 menu from {$source->name}.";
        }

        return "Event created with {$copied} menus from {$source->name}.";
    }

    private function copiedMessage(SalesChannel $source, int $copied): string
    {
        if ($copied === 0) {
            return 'No new menus. Those menus are already on this event.';
        }

        if ($copied === 1) {
            return "Added 1 menu from {$source->name}.";
        }

        return "Added {$copied} menus from {$source->name}.";
    }

    /**
     * @param  array<int, array<string, mixed>>  $addonGroups
     */
    private function syncAddonGroups(MenuModel $menu, array $addonGroups): void
    {
        foreach ($addonGroups as $groupIndex => $groupData) {
            $group = MenuAddonGroup::query()->create([
                'menu_id' => $menu->id,
                'name' => $groupData['name'],
                'selection_type' => $groupData['selection_type'],
                'is_required' => $groupData['is_required'] ?? false,
                'sort_order' => $groupIndex,
            ]);

            foreach ($groupData['options'] as $optionIndex => $optionData) {
                MenuAddonOption::query()->create([
                    'menu_addon_group_id' => $group->id,
                    'name' => $optionData['name'],
                    'price' => $optionData['price'],
                    'is_available' => $optionData['is_available'] ?? true,
                    'sort_order' => $optionIndex,
                ]);
            }
        }
    }
}
