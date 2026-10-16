<?php

namespace App\Services;

use App\Models\OmakaseMenu;
use App\Models\OmakaseSession;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OmakaseService
{
    /**
     * @return array{id: int, name: string, date: string, menus_count: int, menu_preview: list<string>}
     */
    public function summary(OmakaseSession $session): array
    {
        $menus = $this->orderedMenus($session);

        return [
            'id' => $session->id,
            'name' => $session->name,
            'date' => $this->dateString($session),
            'menus_count' => (int) ($session->omakase_menus_count ?? $menus->count()),
            'menu_preview' => $menus->take(3)->pluck('name')->values()->all(),
        ];
    }

    /**
     * @return array{id: int, name: string, date: string, menus_count: int, menus: list<array{id: int, name: string}>}
     */
    public function detail(OmakaseSession $session): array
    {
        $session->load(['omakaseMenus' => fn ($query) => $query->orderBy('id')]);
        $menus = $session->omakaseMenus;

        return [
            'id' => $session->id,
            'name' => $session->name,
            'date' => $this->dateString($session),
            'menus_count' => $menus->count(),
            'menus' => $menus
                ->map(fn (OmakaseMenu $menu) => [
                    'id' => $menu->id,
                    'name' => $menu->name,
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * @return list<array{id: int, name: string, date: string, menus_count: int}>
     */
    public function options(): array
    {
        return OmakaseSession::query()
            ->withCount('omakaseMenus')
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get()
            ->map(fn (OmakaseSession $session) => [
                'id' => $session->id,
                'name' => $session->name,
                'date' => $this->dateString($session),
                'menus_count' => (int) $session->omakase_menus_count,
            ])
            ->values()
            ->all();
    }

    public function create(string $name, string $date, ?OmakaseSession $source = null): OmakaseSession
    {
        return DB::transaction(function () use ($name, $date, $source) {
            $session = OmakaseSession::query()->create([
                'name' => $name,
                'date' => $date,
            ]);

            if ($source !== null) {
                $this->copyMenus($source, $session, skipExisting: false);
            }

            return $session;
        });
    }

    public function update(OmakaseSession $session, string $name, string $date): void
    {
        $session->update([
            'name' => $name,
            'date' => $date,
        ]);
    }

    public function delete(OmakaseSession $session): void
    {
        DB::transaction(function () use ($session) {
            $session->omakaseMenus()->delete();
            $session->delete();
        });
    }

    public function addMenu(OmakaseSession $session, string $name): OmakaseMenu
    {
        return $session->omakaseMenus()->create([
            'name' => $name,
        ]);
    }

    public function renameMenu(OmakaseMenu $menu, string $name): void
    {
        $menu->update([
            'name' => $name,
        ]);
    }

    public function deleteMenu(OmakaseMenu $menu): void
    {
        $menu->delete();
    }

    public function importMenus(OmakaseSession $target, OmakaseSession $source): int
    {
        if ((int) $source->id === (int) $target->id) {
            throw ValidationException::withMessages([
                'source_session_id' => 'Choose a different session to import from.',
            ]);
        }

        return DB::transaction(fn () => $this->copyMenus($source, $target, skipExisting: true));
    }

    private function copyMenus(OmakaseSession $source, OmakaseSession $target, bool $skipExisting): int
    {
        $existing = [];

        if ($skipExisting) {
            $existing = array_fill_keys(
                $target->omakaseMenus()
                    ->pluck('name')
                    ->map(fn (string $name) => $this->normalizeMenuName($name))
                    ->all(),
                true,
            );
        }

        $copied = 0;

        foreach ($source->omakaseMenus()->orderBy('id')->get() as $menu) {
            $key = $this->normalizeMenuName($menu->name);

            if ($skipExisting && isset($existing[$key])) {
                continue;
            }

            OmakaseMenu::query()->create([
                'omakase_session_id' => $target->id,
                'name' => $menu->name,
            ]);

            $existing[$key] = true;
            $copied++;
        }

        return $copied;
    }

    /**
     * @return Collection<int, OmakaseMenu>
     */
    private function orderedMenus(OmakaseSession $session): Collection
    {
        if ($session->relationLoaded('omakaseMenus')) {
            return $session->omakaseMenus->sortBy('id')->values();
        }

        return $session->omakaseMenus()->orderBy('id')->get();
    }

    private function dateString(OmakaseSession $session): string
    {
        $date = $session->getAttribute('date');

        if ($date instanceof \DateTimeInterface) {
            return $date->format('Y-m-d');
        }

        return substr((string) $date, 0, 10);
    }

    private function normalizeMenuName(string $name): string
    {
        return mb_strtolower(trim($name));
    }
}
