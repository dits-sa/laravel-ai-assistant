<?php

namespace LaravelAIAssistant\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Routing\Controller;
use LaravelAIAssistant\Models\Conversation;
use LaravelAIAssistant\Models\ConversationMessage;

class AIConversationController extends Controller
{
    /**
     * Get user's conversations.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $limit = min($request->get('limit', 20), 100);
            $offset = $request->get('offset', 0);
            
            $conversations = Conversation::where('user_id', $user->id)
                ->orderBy('updated_at', 'desc')
                ->offset($offset)
                ->limit($limit)
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $conversations,
                'pagination' => [
                    'limit' => $limit,
                    'offset' => $offset,
                    'total' => Conversation::where('user_id', $user->id)->count()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch conversations',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Create a new conversation.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            $conversation = Conversation::create([
                'user_id' => $user->id,
                'title' => $request->get('title', 'New Conversation'),
                'metadata' => $request->get('metadata', []),
            ]);
            
            return response()->json([
                'success' => true,
                'data' => $conversation,
                'message' => 'Conversation created successfully'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to create conversation',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get a specific conversation with messages.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        try {
            $user = Auth::user();
            
            $conversation = Conversation::where('id', $id)
                ->where('user_id', $user->id)
                ->firstOrFail();
            
            $limit = min($request->get('limit', 50), 100);
            $offset = $request->get('offset', 0);
            
            $messages = ConversationMessage::where('conversation_id', $conversation->id)
                ->orderBy('created_at', 'asc')
                ->offset($offset)
                ->limit($limit)
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'conversation' => $conversation,
                    'messages' => $messages,
                    'pagination' => [
                        'limit' => $limit,
                        'offset' => $offset,
                        'total' => ConversationMessage::where('conversation_id', $conversation->id)->count()
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch conversation',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Delete a conversation.
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $user = Auth::user();
            
            $conversation = Conversation::where('id', $id)
                ->where('user_id', $user->id)
                ->firstOrFail();
            
            // Delete associated messages
            ConversationMessage::where('conversation_id', $conversation->id)->delete();
            
            // Delete conversation
            $conversation->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Conversation deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to delete conversation',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Add a message to a conversation.
     */
    public function addMessage(Request $request, string $id): JsonResponse
    {
        try {
            $user = Auth::user();
            
            $conversation = Conversation::where('id', $id)
                ->where('user_id', $user->id)
                ->firstOrFail();
            
            $message = ConversationMessage::create([
                'conversation_id' => $conversation->id,
                'role' => $request->get('role', 'user'),
                'content' => $request->get('content'),
                'metadata' => $request->get('metadata', []),
            ]);
            
            // Update conversation timestamp
            $conversation->touch();
            
            return response()->json([
                'success' => true,
                'data' => $message,
                'message' => 'Message added successfully'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to add message',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Update conversation title.
     */
    public function updateTitle(Request $request, string $id): JsonResponse
    {
        try {
            $user = Auth::user();
            
            $conversation = Conversation::where('id', $id)
                ->where('user_id', $user->id)
                ->firstOrFail();
            
            $conversation->update([
                'title' => $request->get('title', $conversation->title)
            ]);
            
            return response()->json([
                'success' => true,
                'data' => $conversation,
                'message' => 'Conversation title updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to update conversation title',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
