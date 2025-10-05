<?php

namespace LaravelAIAssistant\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;

class AISecurityMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Check if AI Assistant is enabled
        if (!config('ai-assistant.enabled', true)) {
            return response()->json([
                'success' => false,
                'error' => 'AI Assistant disabled',
                'message' => 'AI Assistant is currently disabled'
            ], 503);
        }

        // Rate limiting
        $this->applyRateLimit($request);

        // IP restrictions
        if (!$this->isIPAllowed($request)) {
            return response()->json([
                'success' => false,
                'error' => 'IP not allowed',
                'message' => 'Your IP address is not allowed to access AI services'
            ], 403);
        }

        // Token validation for AI endpoints
        if ($this->isAITokenEndpoint($request)) {
            $this->validateAIToken($request);
        }

        return $next($request);
    }

    /**
     * Apply rate limiting to the request.
     */
    private function applyRateLimit(Request $request): void
    {
        $key = 'ai_assistant:' . $request->ip();
        $maxAttempts = config('ai-assistant.security.max_requests_per_minute', 60);
        
        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $retryAfter = RateLimiter::availableIn($key);
            
            abort(429, "Too many AI requests. Please try again in {$retryAfter} seconds.");
        }
        
        RateLimiter::hit($key, 60); // 1 minute window
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

    /**
     * Check if this is an AI token endpoint.
     */
    private function isAITokenEndpoint(Request $request): bool
    {
        $path = $request->path();
        return str_contains($path, 'api/ai/') && !str_contains($path, 'auth/');
    }

    /**
     * Validate AI token.
     */
    private function validateAIToken(Request $request): void
    {
        $token = $request->header('X-AI-Token') ?? $request->get('token');
        
        if (!$token) {
            abort(401, 'AI token is required');
        }
        
        $cached = Cache::get("ai_token_{$token}");
        
        if (!$cached) {
            abort(401, 'Invalid AI token');
        }
        
        if (now()->isAfter($cached['expires_at'])) {
            Cache::forget("ai_token_{$token}");
            abort(401, 'AI token has expired');
        }
        
        // Add user context to request
        $request->merge(['ai_user_context' => $cached]);
    }
}
