<?php

namespace App\Http\Controllers;

use App\Models\OmakaseMenu;
use App\Models\OmakaseSession;
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

    public function createOmakaseSession(Request $request)
    {
        $omakaseSession = OmakaseSession::create($request->all());
        return response()->json($omakaseSession);
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
