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
        $user = $request->user();
        $existingProfessional = $user->professional;

        // 🔥 Validación dinámica (si existe perfil, archivos no obligatorios)
        $request->validate([
            'category_id' => 'required|exists:categories,id',
            'document_number' => 'required|string',

            'identity_card' => $existingProfessional
                ? 'nullable|file|mimes:pdf,jpg,jpeg,png'
                : 'required|file|mimes:pdf,jpg,jpeg,png',

            'professional_card' => $existingProfessional
                ? 'nullable|file|mimes:pdf,jpg,jpeg,png'
                : 'required|file|mimes:pdf,jpg,jpeg,png',

            'professional_title' => $existingProfessional
                ? 'nullable|file|mimes:pdf,jpg,jpeg,png'
                : 'required|file|mimes:pdf,jpg,jpeg,png',

            'photo' => $existingProfessional
                ? 'nullable|image|mimes:jpg,jpeg,png'
                : 'required|image|mimes:jpg,jpeg,png',

            'phone' => 'required|string',
            'bio' => 'nullable|string',
            'address' => 'nullable|string',
        ]);

        // 🔥 Mantener archivos anteriores si no se envían nuevos
        $identityPath = $existingProfessional->identity_card ?? null;
        $professionalCardPath = $existingProfessional->professional_card ?? null;
        $professionalTitlePath = $existingProfessional->professional_title ?? null;
        $photoPath = $existingProfessional->photo ?? null;

        if ($request->hasFile('identity_card')) {
            $identityPath = $request->file('identity_card')->store('documents/identity', 'public');
        }

        if ($request->hasFile('professional_card')) {
            $professionalCardPath = $request->file('professional_card')->store('documents/professional_card', 'public');
        }

        if ($request->hasFile('professional_title')) {
            $professionalTitlePath = $request->file('professional_title')->store('documents/professional_title', 'public');
        }

        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('documents/photo', 'public');
        }

        $professional = Professional::updateOrCreate(
            ['user_id' => $user->id],
            [
                'category_id' => $request->category_id,
                'document_number' => $request->document_number,
                'identity_card' => $identityPath,
                'professional_card' => $professionalCardPath,
                'professional_title' => $professionalTitlePath,
                'photo' => $photoPath,
                'phone' => $request->phone,
                'bio' => $request->bio,
                'address' => $request->address,
                'status' => 'pending',
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Perfil enviado correctamente. Pendiente de aprobación.',
            'professional' => $professional
        ]);
    }
}