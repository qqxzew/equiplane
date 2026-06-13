<?php
declare(strict_types=1);

function getPriorityBadge(string $priority): string {
    $color = match($priority) {
        'high' => 'bg-red-500/10 text-red-400 border-red-500/20',
        'medium' => 'bg-yellow-500/10 text-yellow-400 border-yellow-500/20',
        default => 'bg-green-500/10 text-green-400 border-green-500/20',
    };
    $label = ucfirst($priority);
    return "<span class=\"px-2.5 py-1 text-xs font-medium rounded-md border $color\">$label</span>";
}

function getStatusBadge(string $status): string {
    $color = match($status) {
        'in_progress' => 'bg-orange-500/10 text-orange-400 border-orange-500/20',
        'backup_required' => 'bg-red-500/10 text-red-400 border-red-500/20 font-bold',
        'closed' => 'bg-gray-500/10 text-gray-400 border-gray-500/20',
        default => 'bg-blue-500/10 text-blue-400 border-blue-500/20',
    };
    $label = str_replace('_', ' ', ucfirst($status));
    return "<span class=\"px-2.5 py-1 text-xs font-medium rounded-md border $color\">$label</span>";
}