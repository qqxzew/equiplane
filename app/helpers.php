<?php
declare(strict_types=1);

function getPriorityBadge(string $priority): string
{
    $color = match ($priority) {
        'high' => 'bg-red-500/10 text-red-400 border-red-500/20',
        'medium' => 'bg-yellow-500/10 text-yellow-400 border-yellow-500/20',
        default => 'bg-green-500/10 text-green-400 border-green-500/20',
    };
    $label = ucfirst($priority);
    return "<span class=\"px-2.5 py-1 text-xs font-medium rounded-md border $color\">$label</span>";
}

function getStatusBadge(string $status): string
{
    $color = match ($status) {
        'in_progress' => 'bg-orange-500/10 text-orange-400 border-orange-500/20',
        'backup_required' => 'bg-red-500/10 text-red-400 border-red-500/20 font-bold',
        'closed' => 'bg-gray-500/10 text-gray-400 border-gray-500/20',
        default => 'bg-blue-500/10 text-blue-400 border-blue-500/20',
    };
    $label = str_replace('_', ' ', ucfirst($status));
    return "<span class=\"px-2.5 py-1 text-xs font-medium rounded-md border $color\">$label</span>";
}

function setFlash(string $type, string $message): void
{
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message,
    ];
}

function displayFlash(): string
{
    if (!isset($_SESSION['flash'])) {
        return '';
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);

    $colorClass = $flash['type'] === 'success'
        ? 'bg-green-500/10 border-green-500/20 text-green-400'
        : 'bg-red-500/10 border-red-500/20 text-red-400';

    return "<div class=\"border {$colorClass} text-sm p-4 rounded-lg mb-6\">"
        . htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8')
        . "</div>";
}