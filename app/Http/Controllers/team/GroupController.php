<?php

namespace App\Http\Controllers\team;

use App\Http\Controllers\Controller;
use App\Models\Group;
use Illuminate\Http\Request;

class GroupController extends Controller
{
    // Team Leader creates a group
    public function create(Request $request)
    {
        $user = auth()->user();

        if ($user->role !== 'teamleader') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // ✅ FIXED VALIDATION
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $group = Group::create([
            'name' => $request->name,
            'created_by' => $user->id
        ]);

        // ✅ Auto add leader as group member
        $group->members()->attach($user->id);

        return response()->json(
            $group->load('members'),
            201
        );
    }

    // Delete a group
    public function delete($groupId)
    {
        $user = auth()->user();
        $group = Group::findOrFail($groupId);

        // Only the leader who created the group can delete it
        if ($group->created_by !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $group->delete();

        return response()->json(['message' => 'Group deleted successfully']);
    }

    // List all groups for current user
    public function myGroups()
    {
        $user = auth()->user();

        $groups = Group::where('created_by', $user->id)
            ->orWhereHas('members', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->with('members')
            ->get();

        return response()->json($groups);
    }

    // Add member to group
    public function addMember(Request $request, $groupId)
    {
        $user = auth()->user();
        $group = Group::findOrFail($groupId);

        if ($group->created_by !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'user_id' => 'required|exists:users,id'
        ]);

        // ✅ Prevent duplicate attach
        $group->members()->syncWithoutDetaching($request->user_id);

        return response()->json(
            $group->load('members')
        );
    }

    // Remove member from group
public function removeMember(Request $request, $groupId)
{
    $user = auth()->user();
    $group = Group::findOrFail($groupId);

    if ($group->created_by !== $user->id) {
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    $request->validate([
        'user_id' => 'required|exists:users,id'
    ]);

    // Detach member
    $group->members()->detach($request->user_id);

    return response()->json([
        'message' => 'Member removed successfully',
        'group' => $group->load('members')
    ]);
}
}
