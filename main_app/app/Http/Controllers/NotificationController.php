<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NotificationController extends Controller
{
    public function index(Request $request): Response
    {
        return response()->json(
            $request->user()->notifications()->latest()->paginate(5)
        );
    }

    public function markAsRead(Request $request, string|int $id): Response
    {
        $request->user()->notifications()->findOrFail($id)->markAsRead();
        return response()->noContent();
    }

    public function markAllAsRead(Request $request): Response
    {
        $request->user()->notifications()->get()->markAsRead();
        return response()->noContent();
    }
}

