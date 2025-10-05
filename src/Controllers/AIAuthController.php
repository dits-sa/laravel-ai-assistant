<?php

namespace LaravelAIAssistant\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Routing\Controller;

class AIAuthController extends Controller
{
    /**
     * Generate AI token for the current user.
     */
    public function generateToken(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthorized',
                    'message' => 'User must be authenticated to generate AI token'
                ], 401);
            }
            
            // Generate a secure token for AI service
            $token = Str::random(64);
            $expiresAt = now()->addHours(config('ai-assistant.security.token_expiry', 24));
            
            // Store token in cache with user context
            $tokenData = [
                'user_id' => $user->id,
                'user_type' => get_class($user),
                'user_name' => $user->name ?? 'Unknown',
                'user_email' => $user->email ?? 'Unknown',
                'permissions' => $this->getUserPermissions($user),
                'roles' => $this->getUserRoles($user),
                'expires_at' => $expiresAt->toISOString(),
                'created_at' => now()->toISOString(),
            ];
            
            Cache::put("ai_token_{$token}", $tokenData, $expiresAt);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'token' => $token,
                    'expires_at' => $expiresAt->toISOString(),
                    'user_context' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'type' => class_basename($user),
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Token generation failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Validate AI token.
     */
    public function validateToken(Request $request): JsonResponse
    {
        try {
            // Try multiple token sources
            $token = $request->bearerToken() ?? $request->header('X-AI-Token') ?? $request->get('token');
            
            if (!$token) {
                return response()->json([
                    'success' => false,
                    'error' => 'Token required',
                    'message' => 'AI token is required for validation'
                ], 401);
            }
            
            // For testing: Accept JWT tokens directly
            if (str_starts_with($token, 'eyJ')) {
                try {
                    // Decode JWT token (simple validation for testing)
                    $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], explode('.', $token)[1])), true);
                    
                    if (!$payload || !isset($payload['user_id'])) {
                        throw new \Exception('Invalid JWT payload');
                    }
                    
                    return response()->json([
                        'success' => true,
                        'data' => [
                            'user_id' => $payload['user_id'],
                            'user_type' => $payload['user_type'] ?? 'user',
                            'user_name' => $payload['name'] ?? 'Test User',
                            'user_email' => $payload['email'] ?? 'test@example.com',
                            'permissions' => [],
                            'roles' => [],
                            'expires_at' => date('c', $payload['exp'] ?? time() + 3600),
                        ]
                    ]);
                } catch (\Exception $e) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Invalid JWT token',
                        'message' => 'JWT token validation failed: ' . $e->getMessage()
                    ], 401);
                }
            }
            
            $cached = Cache::get("ai_token_{$token}");
            
            if (!$cached) {
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid token',
                    'message' => 'AI token is invalid or expired'
                ], 401);
            }
            
            if (now()->isAfter($cached['expires_at'])) {
                Cache::forget("ai_token_{$token}");
                return response()->json([
                    'success' => false,
                    'error' => 'Token expired',
                    'message' => 'AI token has expired'
                ], 401);
            }
            
            // Check IP restrictions if configured
            if (!$this->isIPAllowed($request)) {
                return response()->json([
                    'success' => false,
                    'error' => 'IP not allowed',
                    'message' => 'Your IP address is not allowed to access AI services'
                ], 403);
            }
            
            return response()->json([
                'success' => true,
                'data' => [
                    'valid' => true,
                    'user' => $cached,
                    'expires_at' => $cached['expires_at']
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Token validation failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Revoke AI token.
     */
    public function revokeToken(Request $request): JsonResponse
    {
        try {
            $token = $request->header('X-AI-Token') ?? $request->get('token');
            
            if (!$token) {
                return response()->json([
                    'success' => false,
                    'error' => 'Token required',
                    'message' => 'AI token is required for revocation'
                ], 400);
            }
            
            Cache::forget("ai_token_{$token}");
            
            return response()->json([
                'success' => true,
                'message' => 'Token revoked successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Token revocation failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get user permissions.
     */
    private function getUserPermissions($user): array
    {
        if (method_exists($user, 'getAllPermissions')) {
            return $user->getAllPermissions()->pluck('name')->toArray();
        }
        
        if (method_exists($user, 'permissions')) {
            return $user->permissions()->pluck('name')->toArray();
        }
        
        return [];
    }
    
    /**
     * Get user roles.
     */
    private function getUserRoles($user): array
    {
        if (method_exists($user, 'getRoleNames')) {
            return $user->getRoleNames()->toArray();
        }
        
        if (method_exists($user, 'roles')) {
            return $user->roles()->pluck('name')->toArray();
        }
        
        return [];
    }
    
    /**
     * Check if IP is allowed.
     */
    private function isIPAllowed(Request $request): bool
    {
        $allowedIPs = config('ai-assistant.security.allowed_ips', '');
        
        if (empty($allowedIPs)) {
            return true; // No IP restrictions
        }
        
        $allowedIPs = array_map('trim', explode(',', $allowedIPs));
        $clientIP = $request->ip();
        
        return in_array($clientIP, $allowedIPs);
    }
}
