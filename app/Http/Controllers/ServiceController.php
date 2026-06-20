<?php

namespace App\Http\Controllers;

use App\Http\Requests\ServiceRequest;
use App\Models\Service;
use Illuminate\Http\JsonResponse;

class ServiceController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => Service::query()->latest()->paginate(20),
        ]);
    }

    public function store(ServiceRequest $request): JsonResponse
    {
        $service = Service::query()->create($request->validated());

        return response()->json(['data' => $service], 201);
    }

    public function show(Service $service): JsonResponse
    {
        return response()->json(['data' => $service]);
    }

    public function update(ServiceRequest $request, Service $service): JsonResponse
    {
        $service->update($request->validated());

        return response()->json(['data' => $service->refresh()]);
    }

    public function destroy(Service $service): JsonResponse
    {
        $service->delete();

        return response()->json(status: 204);
    }
}
