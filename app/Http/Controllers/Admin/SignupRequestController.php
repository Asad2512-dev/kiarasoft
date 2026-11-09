<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SignupRequest;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\ShopOnboardingService;
use Illuminate\Http\Request;

class SignupRequestController extends Controller
{
    public function index(Request $request)
    {
        $status = in_array($request->status, ['pending', 'approved', 'rejected'], true) ? $request->status : 'pending';
        $requests = SignupRequest::with('reviewer')->where('status', $status)->latest()->paginate(15)->withQueryString();

        return view('admin.signups', compact('requests', 'status'));
    }

    public function approve(SignupRequest $signupRequest, Request $request, ShopOnboardingService $onboarding)
    {
        abort_unless($signupRequest->status === 'pending', 422, 'This request was already reviewed.');
        if (User::where('email', $signupRequest->email)->exists()) {
            return back()->with('error', 'A user with this e-mail already exists.');
        }
        $result = $onboarding->onboard($signupRequest->only(['shop_name', 'owner_name', 'email', 'phone', 'city']), $request->user());
        $signupRequest->update(['status' => 'approved', 'reviewed_by' => $request->user()->id, 'reviewed_at' => now(), 'shop_id' => $result['shop']->id]);

        return redirect()->route('admin.shops.show', $result['shop'])
            ->with('credentials', ['email' => $result['admin']->email, 'password' => $result['password']])
            ->with('success', 'Request approved and shop created.');
    }

    public function reject(SignupRequest $signupRequest, Request $request, ActivityLogger $logger)
    {
        abort_unless($signupRequest->status === 'pending', 422, 'This request was already reviewed.');
        $data = $request->validate(['review_note' => ['nullable', 'string', 'max:255']]);
        $signupRequest->update(['status' => 'rejected', 'reviewed_by' => $request->user()->id, 'reviewed_at' => now(), 'review_note' => $data['review_note'] ?? null]);
        $logger->log('signup.rejected', "Signup request from {$signupRequest->shop_name} rejected", $signupRequest);

        return back()->with('success', 'Request rejected.');
    }
}
