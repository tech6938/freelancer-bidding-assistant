<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\FreelancerConnection;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ensure the authenticated user has a valid Freelancer API connection.
 *
 * Eliminates repeated connection checks across controllers.
 * Stores the connection on the request for downstream use.
 */
class EnsureFreelancerConnected
{
    public function handle(Request $request, Closure $next): Response
    {
        $userId = Auth::id();

        if (!$userId) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
            }
            return redirect()->route('login');
        }

        $connection = FreelancerConnection::where('user_id', $userId)->first();

        if (!$connection || empty($connection->access_token)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Freelancer account is not connected.',
                ], 403);
            }
            return redirect()->route('freelancer.index')
                ->with('warning', 'Please connect your Freelancer account first.');
        }

        // Store connection on request for downstream controllers/services
        $request->attributes->set('freelancer_connection', $connection);

        return $next($request);
    }
}
