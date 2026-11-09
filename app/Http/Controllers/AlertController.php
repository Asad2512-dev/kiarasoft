<?php

namespace App\Http\Controllers;

use App\Services\AlertService;
use Illuminate\Http\Request;

class AlertController extends Controller
{
    public function index(Request $request, AlertService $alerts)
    {
        $user = $request->user();
        $branch = $request->integer('branch') ?: null;
        if ($branch) {
            abort_unless($user->canAccessBranch($branch), 403);
        }
        $status = in_array($request->status, ['low', 'out'], true) ? $request->status : null;
        $items = $alerts->query($user, $status, $branch)
            ->when($request->q, fn ($q, $s) => $q->where('products.name', 'like', "%{$s}%"))
            ->paginate(25)->withQueryString();

        return view('alerts.index', ['items' => $items, 'counts' => $alerts->counts($user), 'branches' => $user->accessibleBranches()->get()]);
    }
}
