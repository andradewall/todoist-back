<?php

use App\Models\Token;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/clients/{cnpj}/balance', function (string $cnpj, Request $request) {

    // Lógicas internas

    // Obter token do Gateway
    $token = Token::first();

    $scope = 'admin';

    if (!$token || $token->created_at->addSeconds($token->expires_in) > now()) {
        $tokenResponse = Http::post(
            env('GATEWAY_URL') . '/oauth/token',
            [
                'grant_type' => 'client_credentials',
                'client_id' => env('GATEWAY_CLIENT_ID'),
                'client_secret' => env('GATEWAY_CLIENT_SECRET'),
                'scope' => $scope,
            ]
        )->json();

        Token::updateOrCreate([
            'id' => $token->id ?? null,
        ], [
            'access_token' => $tokenResponse['access_token'],
            'token_type' => $tokenResponse['token_type'],
            'expires_in' => $tokenResponse['expires_in'],
        ]);
    }

    $token = Token::first();

    // Obter saldo
    $saldoResponse = Http::withHeaders([
        'Authorization' => $token->token_type . ' ' . $token->access_token,
    ])
        ->post(env('GATEWAY_URL') . '/api/clients/' . $cnpj . '/balance', [
            'user_id' => $user->id,
        ])
        ->json();

    /**
     * Tratar resposta do Gateway
     * $saldoResponse...
     */

    return [$cnpj, $saldoResponse];
})->middleware('client');
