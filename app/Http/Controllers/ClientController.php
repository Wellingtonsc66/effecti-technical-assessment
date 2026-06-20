<?php

namespace App\Http\Controllers;

use App\Http\Requests\ClientRequest;
use App\Models\Client;
use Illuminate\Http\JsonResponse;

class ClientController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => Client::query()->latest()->paginate(20),
        ]);
    }

    public function store(ClientRequest $request): JsonResponse
    {
        $client = Client::query()->create($request->validated());

        return response()->json(['data' => $client], 201);
    }

    public function show(Client $client): JsonResponse
    {
        return response()->json(['data' => $client->load('contracts')]);
    }

    public function update(ClientRequest $request, Client $client): JsonResponse
    {
        $client->update($request->validated());

        return response()->json(['data' => $client->refresh()]);
    }

    public function destroy(Client $client): JsonResponse
    {
        $client->delete();

        return response()->json(status: 204);
    }
}
