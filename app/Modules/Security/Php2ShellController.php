<?php

namespace App\Modules\Security;

use App\Http\Controllers\Controller;
use App\Modules\Crawler\SafeHttpClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class Php2ShellController extends Controller
{
    public function index(Request $request)
    {
        $workspace = $request->user()->currentWorkspace;
        abort_unless($workspace && $request->user()->hasRole($workspace, ['owner', 'admin', 'specialist', 'viewer']), 403);
        $assessment = Cache::get($this->cacheKey($request));

        return Inertia::render('Security/Php2Shell', [
            'url' => $request->old('url', $assessment['requested_url'] ?? ''),
            'canScan' => $request->user()->hasRole($workspace, ['owner', 'admin', 'specialist']),
            'assessment' => $assessment,
        ]);
    }

    public function scan(Request $request, SafeHttpClient $http, Php2ShellDetector $detector)
    {
        $workspace = $request->user()->currentWorkspace;
        abort_unless($workspace && $request->user()->hasRole($workspace, ['owner', 'admin', 'specialist']), 403);

        // Invalid or failed rechecks must not leave an earlier result looking current.
        Cache::forget($this->cacheKey($request));
        $validated = $request->validate([
            'url' => ['required', 'string', 'max:2048', 'url:http,https'],
        ]);

        try {
            $response = $http->get($validated['url']);
        } catch (\Throwable $exception) {
            return redirect()->route('security.php2shell')->withInput($request->only('url'))
                ->withErrors(['scan' => 'Hedef kontrol edilemedi. Adresi ve internetten erişilebilirliğini doğrulayıp tekrar deneyin.']);
        }

        Cache::put($this->cacheKey($request), $detector->report($response), now()->addDay());

        return redirect()->route('security.php2shell');
    }

    private function cacheKey(Request $request): string
    {
        return "php2shell:url:v1:{$request->user()->current_workspace_id}:{$request->user()->id}";
    }
}
