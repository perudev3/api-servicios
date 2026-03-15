<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ServiceRequest;

class ServiceRequestController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'category_id' => 'required|exists:categories,id',
            'service_id'  => 'required|exists:services,id',
            'description' => 'required|string',
            'address' => 'nullable|string',
            'service_date'=> 'required|date',
            'service_time'=> 'required',
            'budget'      => 'nullable|numeric',
            'city_id' => 'nullable|exists:cities,id',
        ]);

        $serviceRequest = ServiceRequest::create([
            'client_id'    => $request->user()->id, // <- aquí debe ir el ID del cliente logueado
            'category_id'  => $request->category_id,
            'service_id'   => $request->service_id,
            'description'  => $request->description,
            'address'      => $request->address,
            'service_date' => $request->service_date,
            'service_time' => $request->service_time,
            'budget'       => $request->budget,
            'city_id' => $request->city_id,
        ]);

        return response()->json([
            'message' => 'Solicitud creada correctamente',
            'data' => $serviceRequest
        ], 201);
    }


     public function checkStatus(Request $request, $id)
    {
        $serviceRequest = ServiceRequest::with('professional.user')
            ->where('id', $id)
            ->where('client_id', $request->user()->id)
            ->firstOrFail();

        return response()->json([
            'status'       => $serviceRequest->status,
            'professional' => $serviceRequest->professional ? [
                'id'    => $serviceRequest->professional->id,
                'name'  => $serviceRequest->professional->user->name,
                'phone' => $serviceRequest->professional->phone,
                'photo' => $serviceRequest->professional->photo,
            ] : null
        ]);
    }

    // 🔄 POLLING — Profesional consulta solicitudes disponibles en su ciudad/servicio
    public function available(Request $request)
    {
        $professional = $request->user()->professional;

        $requests = ServiceRequest::with('client', 'service')
            ->where('city_id', $professional->city_id)
            ->where('service_id', $professional->service_id)
            ->where('status', 'pending')
            ->whereNull('professional_id')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($requests->map(function($r) {
            return [
                'id'           => $r->id,
                'description'  => $r->description,
                'address'      => $r->address,
                'service_date' => $r->service_date,
                'service_time' => $r->service_time,
                'budget'       => $r->budget,
                'service_name' => $r->service ? $r->service->name : null,
                'client_name'  => $r->client ? $r->client->name : 'Cliente',
            ];
        }));
    }

    // Profesional acepta una solicitud
    public function accept(Request $request, $id)
    {
        $professional = $request->user()->professional;

        $serviceRequest = ServiceRequest::where('id', $id)
            ->where('status', 'pending')
            ->whereNull('professional_id')
            ->firstOrFail();

        $serviceRequest->update([
            'professional_id' => $professional->id,
            'status'          => 'accepted',
        ]);

        return response()->json([
            'message' => 'Solicitud aceptada',
            'data'    => $serviceRequest
        ]);
    }

}