<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportOmakaseMenusRequest;
use App\Http\Requests\StoreOmakaseMenuRequest;
use App\Http\Requests\StoreOmakaseSessionRequest;
use App\Http\Requests\UpdateOmakaseMenuRequest;
use App\Http\Requests\UpdateOmakaseSessionRequest;
use App\Models\OmakaseMenu;
use App\Models\OmakaseSession;
use App\Services\OmakaseService;
use App\Services\StoreHoursService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class OmakaseAdminController extends Controller
{
    public function __construct(private OmakaseService $omakase) {}

    public function index(): Response
    {
        $sessions = OmakaseSession::query()
            ->with(['omakaseMenus' => fn ($query) => $query->orderBy('id')])
            ->withCount('omakaseMenus')
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get();

        return Inertia::render('admin/omakase/index', [
            'today' => Carbon::now(StoreHoursService::TIMEZONE)->toDateString(),
            'sessions' => $sessions
                ->map(fn (OmakaseSession $session) => $this->omakase->summary($session))
                ->values()
                ->all(),
        ]);
    }

    public function create(Request $request): Response
    {
        $from = $request->integer('from');
        $source = $from > 0 ? OmakaseSession::query()->find($from) : null;

        return Inertia::render('admin/omakase/create', [
            'sessions' => $this->omakase->options(),
            'sourceSessionId' => $source?->id,
            'defaults' => [
                'name' => $source?->name ?? '',
                'date' => Carbon::now(StoreHoursService::TIMEZONE)->toDateString(),
            ],
        ]);
    }

    public function store(StoreOmakaseSessionRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $source = null;

        if (! empty($validated['source_session_id'])) {
            $source = OmakaseSession::query()->findOrFail($validated['source_session_id']);
        }

        $session = $this->omakase->create(
            $validated['name'],
            $validated['date'],
            $source,
        );

        $message = 'Omakase session created successfully.';

        if ($source !== null && $source->omakaseMenus()->count() > 0) {
            $message = "Omakase session created with menus from {$source->name}.";
        }

        return redirect()
            ->route('admin.omakase.show', $session)
            ->with('success', $message);
    }

    public function show(OmakaseSession $omakaseSession): Response
    {
        return Inertia::render('admin/omakase/show', [
            'session' => $this->omakase->detail($omakaseSession),
            'importSources' => array_values(array_filter(
                $this->omakase->options(),
                fn (array $option) => $option['id'] !== $omakaseSession->id,
            )),
        ]);
    }

    public function update(UpdateOmakaseSessionRequest $request, OmakaseSession $omakaseSession): RedirectResponse
    {
        $validated = $request->validated();
        $this->omakase->update($omakaseSession, $validated['name'], $validated['date']);

        return redirect()
            ->route('admin.omakase.show', $omakaseSession)
            ->with('success', 'Omakase session updated successfully.');
    }

    public function destroy(OmakaseSession $omakaseSession): RedirectResponse
    {
        $this->omakase->delete($omakaseSession);

        return redirect()
            ->route('admin.omakase.index')
            ->with('success', 'Omakase session deleted successfully.');
    }

    public function importMenus(ImportOmakaseMenusRequest $request, OmakaseSession $omakaseSession): RedirectResponse
    {
        $source = OmakaseSession::query()->findOrFail($request->validated('source_session_id'));
        $available = $source->omakaseMenus()->count();
        $copied = $this->omakase->importMenus($omakaseSession, $source);

        return redirect()
            ->route('admin.omakase.show', $omakaseSession)
            ->with('success', $this->importMessage($source, $available, $copied));
    }

    public function storeMenu(StoreOmakaseMenuRequest $request, OmakaseSession $omakaseSession): RedirectResponse
    {
        $this->omakase->addMenu($omakaseSession, $request->validated('menu_name'));

        return redirect()
            ->route('admin.omakase.show', $omakaseSession)
            ->with('success', 'Menu added successfully.');
    }

    public function updateMenu(
        UpdateOmakaseMenuRequest $request,
        OmakaseSession $omakaseSession,
        OmakaseMenu $omakaseMenu,
    ): RedirectResponse {
        $this->omakase->renameMenu(
            $this->menuOnSession($omakaseSession, $omakaseMenu),
            $request->validated('menu_name'),
        );

        return redirect()
            ->route('admin.omakase.show', $omakaseSession)
            ->with('success', 'Menu updated successfully.');
    }

    public function destroyMenu(OmakaseSession $omakaseSession, OmakaseMenu $omakaseMenu): RedirectResponse
    {
        $this->omakase->deleteMenu($this->menuOnSession($omakaseSession, $omakaseMenu));

        return redirect()
            ->route('admin.omakase.show', $omakaseSession)
            ->with('success', 'Menu deleted successfully.');
    }

    private function menuOnSession(OmakaseSession $session, OmakaseMenu $menu): OmakaseMenu
    {
        abort_unless((int) $menu->omakase_session_id === (int) $session->id, 404);

        return $menu;
    }

    private function importMessage(OmakaseSession $source, int $available, int $copied): string
    {
        if ($available === 0) {
            return 'That session has no menus to import.';
        }

        if ($copied === 0) {
            return 'No new menus. Those names are already on this session.';
        }

        if ($copied === 1) {
            return "Imported 1 menu from {$source->name}.";
        }

        return "Imported {$copied} menus from {$source->name}.";
    }
}
