<?php

namespace App\Services\MenuItem;

use App\Models\MenuItem;
use App\Models\MenuItemImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

use Intervention\Image\Facades\Image;

class MenuItemImageService
{
    public function getImagesByMenuItem(MenuItem $menuItem)
    {
        return $menuItem->images()->orderBy('order')->orderBy('id')->get();
    }

    public function uploadImage(MenuItem $menuItem, UploadedFile $file, ?string $altText = null, ?int $order = 0): MenuItemImage
    {
        $originalFilename = $file->getClientOriginalName();
        $hashPart = substr(md5(uniqid((string) time(), true)), 0, 8);

        $extension = 'webp';
        $filename = pathinfo($originalFilename, PATHINFO_FILENAME);
        $storageFilename = sprintf('%s_%s.%s', Str::slug($filename), $hashPart, $extension);
        $storagePath = "menu_items/{$menuItem->id}/{$storageFilename}";

        $image = Image::make($file)->encode('webp', 90);
        Storage::disk('public')->put($storagePath, (string) $image);

        return MenuItemImage::create([
            'menu_item_id' => $menuItem->id,
            'original_filename' => $originalFilename,
            'url' => Storage::disk('public')->url($storagePath),
            'format' => 'webp',
            'hash' => $hashPart,
            'alt_text' => $altText,
            'order' => $order ?? 0,
        ]);
    }

    public function deleteImage(MenuItemImage $image): void
    {
        $path = str_replace('/storage/', '', parse_url($image->url, PHP_URL_PATH));
        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }

        $image->delete();
    }

    public function reorderImages(MenuItem $menuItem, array $orderedIds): void
    {
        foreach ($orderedIds as $index => $id) {
            $image = $menuItem->images()->where('id', $id)->first();
            if ($image) {
                $image->update(['order' => $index]);
            }
        }
    }
}
