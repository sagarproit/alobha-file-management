<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Repository;
use App\Models\User;

class RepositoryController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $ownedRepos = $user->repositories()->get();  // Repositories created by user
        $sharedRepos = $user->sharedRepositories()->get();  // Repositories shared with user via role

        // Merge and remove duplicates by ID
        $allRepos = $ownedRepos->merge($sharedRepos)->unique('id')->values();

        return response()->json($allRepos);
    }


    public function store(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $request->validate([
            'name' => 'required|string|max:255'
        ]);

        $repository = Repository::create([
            'user_id' => $user->id,
            'name' => $request->name
        ]);

        return response()->json([
            'message' => 'Repository created successfully',
            'repository' => $repository
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $request->validate(['name' => 'required|string|max:255']);

        $repo = Repository::where('user_id', $user->id)->findOrFail($id);
        $repo->update(['name' => $request->name]);

        return response()->json(['message' => 'Repository renamed successfully']);
    }

    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $repo = Repository::where('user_id', $user->id)->findOrFail($id);
        $repo->delete();

        return response()->json(['message' => 'Repository deleted successfully']);
    }

    public function assignRole(Request $request, $id)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'required|in:admin,write,read',
        ]);

        $repository = Repository::findOrFail($id);

        if ($repository->user_id !== $request->user()->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $repository->collaborators()->syncWithoutDetaching([
            $request->user_id => ['role' => $request->role]
        ]);
        logRepoAction($repository->id, $request->user()->id, 'assign_role', "Assigned role '{$request->role}' to user {$request->user_id}");

        return response()->json(['message' => 'Role assigned successfully']);
    }

    public function listUsers(Request $request)
    {
        return User::where('id', '!=', $request->user()->id)->select('id', 'name')->get();
    }
}
