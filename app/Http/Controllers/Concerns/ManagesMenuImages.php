<?php

namespace App\Http\Controllers\Concerns;

use App\Http\Requests\StoreMenuRequest;
use App\Services\MenuImageService;

trait ManagesMenuImages
{
    protected function resolveMenuImageUrl(
        StoreMenuRequest $request,
        MenuImageService $menuImages,
        ?string $currentImageUrl = null,
    ): ?string {
        if ($request->boolean('remove_image')) {
            $menuImages->deleteIfStored($currentImageUrl);

            return null;
        }

        if ($request->hasFile('image')) {
            $menuImages->deleteIfStored($currentImageUrl);

            return $menuImages->upload($request->file('image'));
        }

        return $currentImageUrl;
    }
}
