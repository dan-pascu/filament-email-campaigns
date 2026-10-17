<?php

namespace DanPascu\FilamentEmailCampaigns\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadExportController extends Controller
{
    public function __invoke(Request $request): StreamedResponse
    {
        $path = base64_decode((string) $request->query('path'), strict: true);
        $disk = Storage::disk(config('filament-email-campaigns.storage.disk', 'local'));

        abort_unless($path && $disk->exists($path), 404);

        return $disk->download($path);
    }
}
