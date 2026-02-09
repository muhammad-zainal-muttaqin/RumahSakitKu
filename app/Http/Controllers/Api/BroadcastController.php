<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use Exception;
use App\Models\MasterData\Polyclinic;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Broadcast Controller
 * 
 * Handles broadcast-related API endpoints for Laravel Echo authentication
 * and channel management.
 */
class BroadcastController extends BaseController
{
    /**
     * Authenticate a private/presence channel request from Laravel Echo.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function auth(Request $request): JsonResponse
    {
        try {
            // Validate the request
            $request->validate([
                'channel_name' => 'required|string',
                'socket_id' => 'required|string',
            ]);

            $channelName = $request->input('channel_name');
            $socketId = $request->input('socket_id');

            // Check if user is authenticated
            if (!$request->user()) {
                return $this->sendError(
                    message: 'Unauthenticated',
                    statusCode: Response::HTTP_UNAUTHORIZED
                );
            }

            // Generate the authentication signature
            $auth = Broadcast::auth($request);

            Log::info('Broadcast auth successful', [
                'user_id' => $request->user()->id,
                'channel' => $channelName,
                'socket_id' => $socketId,
            ]);

            return response()->json($auth);
        } catch (Exception $e) {
            Log::error('Broadcast auth failed', [
                'user_id' => $request->user()?->id,
                'channel' => $request->input('channel_name'),
                'error' => $e->getMessage(),
            ]);

            return $this->sendError(
                message: 'Channel authorization failed: ' . $e->getMessage(),
                statusCode: Response::HTTP_FORBIDDEN
            );
        }
    }

    /**
     * Get list of authorized channels for the authenticated user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function channels(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return $this->sendError(
                message: 'Unauthenticated',
                statusCode: Response::HTTP_UNAUTHORIZED
            );
        }

        $channels = [];

        // Private user channel
        $channels[] = [
            'name' => 'App.Models.User.' . $user->id,
            'type' => 'private',
            'authorized' => true,
        ];

        // Polyclinic channels (if user can view queues)
        if ($user->can('view_queues') || $user->can('manage_queues')) {
            $polyclinics = Polyclinic::active()
                ->select('id', 'name')
                ->get();

            foreach ($polyclinics as $polyclinic) {
                $channels[] = [
                    'name' => 'polyclinic.' . $polyclinic->id,
                    'type' => 'presence',
                    'authorized' => true,
                    'meta' => ['polyclinic_name' => $polyclinic->name],
                ];
            }
        }

        // Emergency channel
        if ($user->hasRole('admin') || $user->hasRole('dokter') || $user->hasRole('perawat')) {
            $channels[] = [
                'name' => 'emergency',
                'type' => 'presence',
                'authorized' => true,
            ];
        }

        // Surgery channels
        if ($user->can('view_surgeries') || $user->can('manage_surgeries')) {
            $channels[] = [
                'name' => 'surgery.updates',
                'type' => 'presence',
                'authorized' => true,
            ];
        }

        // Inpatient channel
        if ($user->can('view_inpatients') || $user->can('manage_inpatients')) {
            $channels[] = [
                'name' => 'inpatient',
                'type' => 'presence',
                'authorized' => true,
            ];
            $channels[] = [
                'name' => 'rooms.occupancy',
                'type' => 'presence',
                'authorized' => true,
            ];
        }

        // Triage channel
        if ($user->can('view_triage') || $user->hasRole('dokter') || $user->hasRole('perawat')) {
            $channels[] = [
                'name' => 'triage',
                'type' => 'presence',
                'authorized' => true,
            ];
        }

        Log::info('Retrieved authorized channels', [
            'user_id' => $user->id,
            'channel_count' => count($channels),
        ]);

        return $this->sendResponse(
            data: [
                'channels' => $channels,
                'broadcast_driver' => config('broadcasting.default'),
                'echo_config' => $this->getEchoConfig(),
            ],
            message: 'Authorized channels retrieved successfully'
        );
    }

    /**
     * Get Echo configuration for frontend.
     *
     * @return array<string, mixed>
     */
    private function getEchoConfig(): array
    {
        $driver = config('broadcasting.default');

        $config = [
            'driver' => $driver,
            'auth_endpoint' => route('broadcast.auth'),
        ];

        switch ($driver) {
            case 'pusher':
                $pusherConfig = config('broadcasting.connections.pusher');
                $config['key'] = $pusherConfig['key'] ?? null;
                $config['cluster'] = $pusherConfig['options']['cluster'] ?? null;
                $config['force_tls'] = $pusherConfig['options']['use_tls'] ?? true;
                $config['auth_endpoint'] = route('broadcast.auth');
                break;

            case 'ably':
                $ablyConfig = config('broadcasting.connections.ably');
                $config['key'] = $ablyConfig['key'] ?? null;
                break;

            case 'reverb':
                $reverbConfig = config('broadcasting.connections.reverb');
                $config['key'] = $reverbConfig['app_key'] ?? null;
                $config['host'] = $reverbConfig['host'] ?? null;
                $config['port'] = $reverbConfig['port'] ?? 8080;
                break;
        }

        return $config;
    }

    /**
     * Webhook for receiving broadcast events from external services.
     * Used for Pusher/Ably webhooks.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function webhook(Request $request): JsonResponse
    {
        $payload = $request->all();

        Log::info('Broadcast webhook received', [
            'driver' => config('broadcasting.default'),
            'payload' => $payload,
        ]);

        // Process webhook events based on driver
        $driver = config('broadcasting.default');

        if ($driver === 'pusher') {
            $this->processPusherWebhook($payload);
        } elseif ($driver === 'ably') {
            $this->processAblyWebhook($payload);
        }

        return $this->sendResponse(
            data: ['received' => true],
            message: 'Webhook processed'
        );
    }

    /**
     * Process Pusher webhook events.
     *
     * @param array<string, mixed> $payload
     * @return void
     */
    private function processPusherWebhook(array $payload): void
    {
        $events = $payload['events'] ?? [];

        foreach ($events as $event) {
            match ($event['name'] ?? null) {
                'channel_occupied' => Log::info('Channel occupied', ['channel' => $event['channel']]),
                'channel_vacated' => Log::info('Channel vacated', ['channel' => $event['channel']]),
                'member_added' => Log::info('Member added', ['channel' => $event['channel'], 'user_id' => $event['user_id']]),
                'member_removed' => Log::info('Member removed', ['channel' => $event['channel'], 'user_id' => $event['user_id']]),
                default => Log::debug('Unknown Pusher event', ['event' => $event]),
            };
        }
    }

    /**
     * Process Ably webhook events.
     *
     * @param array<string, mixed> $payload
     * @return void
     */
    private function processAblyWebhook(array $payload): void
    {
        // Ably-specific webhook processing
        Log::debug('Ably webhook payload', ['payload' => $payload]);
    }
}
