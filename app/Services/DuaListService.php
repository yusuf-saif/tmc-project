<?php

namespace App\Services;

use App\Models\DuaListItem;
use App\Models\Resource;
use App\Models\User;

class DuaListService
{
    public function save(User $user, Resource $resource): void
    {
        $item = DuaListItem::withTrashed()->firstOrNew([
            'user_id' => $user->id,
            'resource_id' => $resource->id,
        ]);

        $item->dua_text = $resource->body ?? $resource->title;

        if ($item->trashed()) {
            $item->restore();
        }

        $item->save();
    }

    public function saveManual(User $user, string $text, ?string $label = null): void
    {
        $item = DuaListItem::withTrashed()->firstOrNew([
            'user_id' => $user->id,
            'resource_id' => null,
            'dua_text' => $text,
            'label' => $label,
        ]);

        if ($item->trashed()) {
            $item->restore();
        }

        $item->save();
    }

    public function remove(User $user, DuaListItem $item): void
    {
        if ($item->user_id !== $user->id) {
            abort(403);
        }

        $item->delete();
    }

    public function isSaved(User $user, Resource $resource): bool
    {
        return DuaListItem::query()
            ->where('user_id', $user->id)
            ->where('resource_id', $resource->id)
            ->exists();
    }
}
