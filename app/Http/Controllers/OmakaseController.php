<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportOmakaseMenusRequest;
use App\Models\OmakaseMenu;
use App\Models\OmakaseSession;
use App\Services\OmakaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OmakaseController extends Controller
{
    public function getOmakaseSessions()
    {
        $omakaseSessions = OmakaseSession::orderBy('created_at', 'desc')->get();

        return response()->json($omakaseSessions);
    }

    public function getOmakaseSessionById($id)
    {
        $omakaseSession = OmakaseSession::findOrFail($id);
        $omakaseSession->load('omakaseMenus');

        return response()->json(
            $omakaseSession,
            200
        );
    }

    public function createOmakaseSession(Request $request, OmakaseService $omakase): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'date' => ['required', 'date'],
            'source_session_id' => ['nullable', 'integer', 'exists:omakase_session,id'],
        ]);

        $source = isset($validated['source_session_id'])
            ? OmakaseSession::query()->find($validated['source_session_id'])
            : null;

        $session = $omakase->create(trim($validated['name']), $validated['date'], $source);

        return response()->json($session->load('omakaseMenus'));
    }

    public function importOmakaseMenus(ImportOmakaseMenusRequest $request, int $id, OmakaseService $omakase): JsonResponse
    {
        $target = OmakaseSession::query()->findOrFail($id);
        $source = OmakaseSession::query()->findOrFail($request->validated('source_session_id'));
        $copied = $omakase->importMenus($target, $source);
        $target->load(['omakaseMenus' => fn ($query) => $query->orderBy('id')]);

        return response()->json([
            'success' => true,
            'copied' => $copied,
            'omakase_menus' => $target->omakaseMenus,
        ]);
    }

    public function deleteOmakaseSession($id)
    {
        $omakaseSession = OmakaseSession::findOrFail($id);
        $omakaseSession->delete();

        return response()->json(['message' => 'Omakase session deleted successfully']);
    }

    public function updateOmakaseSession(Request $request, $id)
    {
        $omakaseSession = OmakaseSession::findOrFail($id);
        $omakaseSession->update($request->all());

        return response()->json($omakaseSession);
    }

    public function createOmakaseMenu(Request $request)
    {
        $omakaseMenu = OmakaseMenu::create($request->all());

        return response()->json($omakaseMenu);
    }

    public function deleteOmakaseMenu($id)
    {
        $omakaseMenu = OmakaseMenu::findOrFail($id);
        $omakaseMenu->delete();

        return response()->json(['message' => 'Omakase menu deleted successfully']);
    }

    public function updateOmakaseMenu(Request $request, $id)
    {
        $omakaseMenu = OmakaseMenu::findOrFail($id);
        $omakaseMenu->update($request->all());

        return response()->json($omakaseMenu);
    }
}
