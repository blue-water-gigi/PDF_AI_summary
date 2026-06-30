<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class AdminController extends Controller
{
    use AuthorizesRequests;

    public function users(): InertiaResponse
    {
        $users = User::with('plan')
            ->withCount('pdfSummaries')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return Inertia::render('admin/users', [
            'users' => $users,
        ]);
    }

    public function updateUserPlan(Request $request, User $user): RedirectResponse
    {
        $request->validate([
            'plan_id' => ['required', Rule::exists('plans', 'id')],
        ]);

        $user->update([
            'plan_id' => $request->plan_id,
            'pdf_count' => 0,
            'pdf_count_resets_at' => now()->addMonth(),
        ]);

        return back()->with('success', 'User Plan has been updated.');
    }
}
