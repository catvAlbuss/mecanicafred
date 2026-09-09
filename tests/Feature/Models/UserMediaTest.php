<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('keeps only the latest user avatar', function () {
    Storage::fake('public');
    $user = User::factory()->create();
    $pngContent = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=', true);

    $user->addMedia(UploadedFile::fake()->createWithContent('first-avatar.png', $pngContent))
        ->toMediaCollection('avatar');
    $user->addMedia(UploadedFile::fake()->createWithContent('latest-avatar.png', $pngContent))
        ->toMediaCollection('avatar');

    expect($user->fresh()->getMedia('avatar'))
        ->toHaveCount(1)
        ->first()->file_name->toBe('latest-avatar.png');
});
