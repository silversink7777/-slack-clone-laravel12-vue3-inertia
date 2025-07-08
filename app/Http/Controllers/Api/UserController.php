<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    protected $userRepository;

    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * ユーザー一覧を取得
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = [];
            if ($request->has('search')) {
                $filters['search'] = $request->get('search');
            }

            $perPage = $request->get('per_page', 20);
            $users = $this->userRepository->getPaginated($filters, $perPage);

            return response()->json($users);

        } catch (\Exception $e) {
            Log::error('Failed to get users', ['error' => $e->getMessage()]);
            
            return response()->json([
                'error' => 'Failed to get users',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 特定のユーザー情報を取得
     */
    public function show(string $id): JsonResponse
    {
        try {
            $user = $this->userRepository->findByIdWithSelect((int)$id, ['id', 'name', 'email']);

            if (!$user) {
                return response()->json(['error' => 'User not found'], 404);
            }

            return response()->json($user);

        } catch (\Exception $e) {
            Log::error('Failed to get user', ['error' => $e->getMessage(), 'id' => $id]);
            
            return response()->json([
                'error' => 'Failed to get user',
                'message' => $e->getMessage()
            ], 500);
        }
    }
} 