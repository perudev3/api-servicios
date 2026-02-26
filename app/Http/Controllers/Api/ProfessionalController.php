<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Professional;

class ProfessionalController extends Controller
{
    public function dashboard(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'needs_profile_completion' => !$user->professional,
            'professional' => $user->professional
        ]);
    }

    public function storeOrUpdate(Request $request)
    {
        $request->validate([
            'category_id' => 'required|exists:categories,id',
            'document_number' => 'required|string',
            'phone' => 'required|string',
            'bio' => 'nullable|string',
            'address' => 'nullable|string',
        ]);

        $professional = Professional::updateOrCreate(
            ['user_id' => $request->user()->id],
            [
                'category_id' => $request->category_id,
                'document_number' => $request->document_number,
                'phone' => $request->phone,
                'bio' => $request->bio,
                'address' => $request->address,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Perfil profesional guardado correctamente.',
            'professional' => $professional
        ]);
    }
}
