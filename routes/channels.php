<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Private user notification channel
Broadcast::channel('user.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

// Workflow instance channel
Broadcast::channel('workflow.{workflowId}', function ($user, $workflowId) {
    // Check if user has access to this workflow
    $workflowInstance = \App\Models\WorkflowInstance::find($workflowId);
    
    if (!$workflowInstance) {
        return false;
    }
    
    // Allow if user created the workflow or is assigned to any step
    return $workflowInstance->created_by === $user->id ||
           $workflowInstance->steps()->where('assignee_id', $user->id)->exists();
});

// Company channel
Broadcast::channel('company.{companyId}', function ($user, $companyId) {
    return $user->companies()->where('companies.id', $companyId)->exists();
});

// Project channel
Broadcast::channel('project.{projectId}', function ($user, $projectId) {
    return $user->projects()->where('projects.id', $projectId)->exists();
});

// Team presence channel
Broadcast::channel('presence.team.{teamId}', function ($user, $teamId) {
    // Check if user belongs to this team
    $team = \App\Models\Company::find($teamId);
    
    if (!$team || !$user->companies()->where('companies.id', $teamId)->exists()) {
        return false;
    }
    
    return [
        'id' => $user->id,
        'name' => $user->name,
        'avatar' => $user->avatar,
        'status' => 'online',
    ];
});

// Global presence channel for online users
Broadcast::channel('presence.global', function ($user) {
    return [
        'id' => $user->id,
        'name' => $user->name,
        'avatar' => $user->avatar,
        'status' => 'online',
    ];
});

// Task updates channel
Broadcast::channel('task.{taskId}', function ($user, $taskId) {
    $task = \App\Models\Task::find($taskId);
    
    if (!$task) {
        return false;
    }
    
    // Allow if user is assigned to task or is part of the project
    return $task->assigned_to === $user->id ||
           $user->projects()->where('projects.id', $task->project_id)->exists();
});

// System announcements (all authenticated users)
Broadcast::channel('announcements', function ($user) {
    return ['id' => $user->id, 'name' => $user->name];
});
