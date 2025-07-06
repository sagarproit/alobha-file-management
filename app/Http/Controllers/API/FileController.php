<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Repository;
use App\Models\File;
use App\Models\FileVersion;
use App\Models\RepositoryUserRole;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class FileController extends Controller
{
    /**
     * Upload a new file or new version.
     */
    public function upload(Request $request, $repositoryId)
    {
        $request->validate([
            'file' => 'required|file|max:10240|mimes:txt,md,json', // Only allow specific MIME types
        ]);


        $user = $request->user();
        $repo = Repository::findOrFail($repositoryId);

        $isOwner = $repo->user_id === $user->id;
        $role = $user->getRepositoryRole($repositoryId);

        if (!$isOwner && !in_array($role, ['write', 'admin'])) {
            return response()->json(['error' => 'Unauthorized (no upload permission)'], 403);
        }

        $uploadedFile = $request->file('file');
        $originalName = $request->input('file_name') ?? $uploadedFile->getClientOriginalName();


        DB::beginTransaction();

        try {
            // 👇 Locking existing file row for concurrency-safe versioning
            $file = File::where('repository_id', $repo->id)
                        ->where('name', $originalName)
                        ->lockForUpdate()
                        ->first();

            if (!$file) {
                $file = File::create([
                    'repository_id' => $repo->id,
                    'name' => $originalName,
                ]);
            }

            $version = $file->versions()->count() + 1;

            $safeName = Str::slug(pathinfo($originalName, PATHINFO_FILENAME));
            $extension = $uploadedFile->getClientOriginalExtension();
            $versionedName = "{$safeName}_v{$version}.{$extension}";

            $path = $uploadedFile->storeAs("repositories/{$repositoryId}", $versionedName, 'public');

            $fileVersion = FileVersion::create([
                'file_id' => $file->id,
                'version_number' => $version,
                'file_path' => $path,
                'uploaded_by' => $user->id,
            ]);

            $file->latest_version_id = $fileVersion->id;
            $file->save();

            DB::commit();

            logRepoAction($repo->id, $user->id, 'upload', "Uploaded file '{$originalName}' as v{$version}");

            return response()->json([
                'message' => 'File uploaded successfully',
                'file' => $file,
                'version' => $fileVersion
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            // Optional: Handle deadlock or race errors explicitly
            if (str_contains($e->getMessage(), 'Deadlock')) {
                return response()->json(['error' => 'Concurrent upload detected. Please retry.'], 409);
            }

            return response()->json(['error' => 'Upload failed', 'details' => $e->getMessage()], 500);
        }
    }
    /**
     * List all files with latest version in a repository
     */
    public function listFiles(Request $request, $repositoryId)
    {
        $user = $request->user();

        $repository = Repository::findOrFail($repositoryId);

        // Check if user is owner or has a role
        $isOwner = $repository->user_id === $user->id;

        $role = $user->getRepositoryRole($repositoryId); // 'read', 'write', 'admin', or null

        if (!$isOwner && !in_array($role, ['read', 'write', 'admin'])) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        $files = $repository->files()->with('latestVersion')->get();

        return response()->json($files);
    }


    public function listVersions(Request $request, $fileId)
    {
        $user = $request->user();

        // Load file and its repository
        $file = File::with('repository')->findOrFail($fileId);
        $repository = $file->repository;

        // Check access
        $isOwner = $repository->user_id === $user->id;
        $role = $user->getRepositoryRole($repository->id); // 'read', 'write', 'admin' or null

        if (!$isOwner && !in_array($role, ['read', 'write', 'admin'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Fetch all versions with uploader info
        $versions = $file->versions()
            ->with('uploader:id,name') // eager load uploader info
            ->orderBy('version_number', 'desc')
            ->get();

        return response()->json([
            'file' => $file->name,
            'versions' => $versions,
        ]);
    }


    /**
     * Download specific version of a file
     */
    public function downloadVersion(Request $request, $fileId, $versionNumber)
    {
        $version = FileVersion::where('file_id', $fileId)
                    ->where('version_number', $versionNumber)
                    ->firstOrFail();

        // Get the file and its repository
        $file = $version->file()->with('repository')->first();
        if (!$file) {
            return response()->json(['error' => 'File not found'], 404);
        }

        $repository = $file->repository;
        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Check if user is owner or has at least 'read' role
        $isOwner = $repository->user_id === $user->id;
        $role = $user->getRepositoryRole($repository->id);

        if (!$isOwner && !in_array($role, ['read', 'write', 'admin'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        // Return download response
        return Storage::disk('public')->download($version->file_path);
    }

    public function compareVersions(Request $request, $fileId, $versionA, $versionB)
    {
        $user = $request->user();

        $file = File::with('repository')->findOrFail($fileId);
        $repo = $file->repository;

        // ✅ Access check: owner or shared with at least read access
        $isOwner = $repo->user_id === $user->id;
        $role = $user->getRepositoryRole($repo->id);

        if (!$isOwner && !in_array($role, ['read', 'write', 'admin'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $verA = $file->versions()->where('version_number', $versionA)->firstOrFail();
        $verB = $file->versions()->where('version_number', $versionB)->firstOrFail();

        return response()->json([
            'version_a' => Storage::disk('public')->get($verA->file_path),
            'version_b' => Storage::disk('public')->get($verB->file_path),
            'name' => $file->name
        ]);
    }

    public function preview(Request $request, $fileId)
    {
        $file = File::with('latestVersion', 'repository')->findOrFail($fileId);
        $user = $request->user();

        $isOwner = $file->repository->user_id === $user->id;
        $role = $user->getRepositoryRole($file->repository_id);

        if (!$isOwner && !in_array($role, ['read', 'write', 'admin'])) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $path = $file->latestVersion->file_path;
        $extension = pathinfo($path, PATHINFO_EXTENSION);

        // Only allow safe preview types
        if (!in_array($extension, ['txt', 'md', 'json'])) {
            return response()->json(['error' => 'Unsupported preview type'], 400);
        }

        $content = Storage::disk('public')->get($path);

        return response()->json([
            'filename' => $file->name,
            'extension' => $extension,
            'content' => $content,
        ]);
    }
    public function previewVersion(Request $request, $fileId, $versionNumber)
    {
        $file = File::with('repository')->findOrFail($fileId);
        $user = $request->user();

        $isOwner = $file->repository->user_id === $user->id;
        $role = $user->getRepositoryRole($file->repository_id);

        if (!$isOwner && !in_array($role, ['read', 'write', 'admin'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $version = FileVersion::where('file_id', $fileId)
                            ->where('version_number', $versionNumber)
                            ->firstOrFail();

        $path = $version->file_path;
        $extension = pathinfo($path, PATHINFO_EXTENSION);

        if (!in_array($extension, ['txt', 'json', 'md'])) {
            return response()->json(['error' => 'Unsupported preview type'], 400);
        }

        $content = Storage::disk('public')->get($path);

        return response()->json([
            'filename' => $file->name,
            'version' => $versionNumber,
            'extension' => $extension,
            'content' => $content,
        ]);
    }

    public function searchFiles(Request $request)
    {
        $user = $request->user();
        $query = trim($request->input('query'));

        if (!$query) {
            return response()->json(['files' => []]);
        }

        // ✅ Get repositories where user has a role
        $roleRepoIds = RepositoryUserRole::where('user_id', $user->id)
            ->whereIn('role', ['read', 'write', 'admin'])
            ->pluck('repository_id')
            ->toArray();

        // ✅ Get repositories where user is the owner
        $ownedRepoIds = Repository::where('user_id', $user->id)
            ->pluck('id')
            ->toArray();

        // ✅ Merge both sets
        $accessibleRepoIds = array_unique(array_merge($roleRepoIds, $ownedRepoIds));

        if (empty($accessibleRepoIds)) {
            return response()->json(['files' => []]);
        }

        // ✅ Search by filename
        $filesByName = File::whereIn('repository_id', $accessibleRepoIds)
            ->where('name', 'LIKE', "%{$query}%")
            ->with('latestVersion')
            ->get();

        // ✅ Search by content (only txt/md/json)
        $contentMatched = collect();

        foreach ($accessibleRepoIds as $repoId) {
            $repoPath = storage_path("app/public/repositories/{$repoId}");
            if (!is_dir($repoPath)) continue;

            foreach (scandir($repoPath) as $fileName) {
                if (in_array(pathinfo($fileName, PATHINFO_EXTENSION), ['txt', 'md', 'json'])) {
                    $filePath = "{$repoPath}/{$fileName}";
                    $content = @file_get_contents($filePath);

                    if ($content && stripos($content, $query) !== false) {
                        $matchedVersion = FileVersion::where('file_path', "repositories/{$repoId}/{$fileName}")
                            ->orderByDesc('version_number') // just in case
                            ->first();

                        if ($matchedVersion && $matchedVersion->file) {
                            $matched = $matchedVersion->file->load('latestVersion');
                            $contentMatched->push($matched);
                        }

                    }
                }
            }
        }

        // ✅ Merge and deduplicate
        $allFiles = $filesByName->merge($contentMatched)->unique('id')->values();

        return response()->json(['files' => $allFiles]);
    }



}
