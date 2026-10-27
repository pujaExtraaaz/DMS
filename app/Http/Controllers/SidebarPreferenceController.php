<?php

namespace App\Http\Controllers;

use App\Support\SidebarManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SidebarPreferenceController extends Controller
{
    /**
     * Update the authenticated user's sidebar section order.
     */
    public function update(Request $request): JsonResponse
    {
        $rawSections = $request->input('sections', $request->input('order'));
        $request->merge(['sections' => $rawSections]);

        $request->validate([
            'sections' => ['required', 'array', 'min:1'],
            'sections.*' => ['required', 'string', Rule::in(SidebarManager::DEFAULT_SECTIONS)],
        ]);

        $user = $request->user();
        $savedOrder = SidebarManager::saveUserOrder($user, $request->input('sections'));

        return response()->json([
            'success' => true,
            'message' => 'Sidebar menu order saved successfully.',
            'sections' => $savedOrder,
            'order' => $savedOrder,
        ]);
    }

    /**
     * Reset the authenticated user's sidebar section order to default.
     */
    public function reset(Request $request): JsonResponse
    {
        $user = $request->user();
        SidebarManager::resetUserOrder($user);

        return response()->json([
            'success' => true,
            'message' => 'Sidebar menu order reset to default.',
            'sections' => SidebarManager::DEFAULT_SECTIONS,
            'order' => SidebarManager::DEFAULT_SECTIONS,
        ]);
    }
}
