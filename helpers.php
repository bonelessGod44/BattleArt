<?php
function timeAgo($timestamp) {
    if ($timestamp === null) {
        return 'Never';
    }
    
    try {
        $datetime = new DateTime($timestamp);
        $now = new DateTime();
        $diff = $now->diff($datetime);

        if ($diff->y > 0) return $diff->y . ' year' . ($diff->y > 1 ? 's' : '') . ' ago';
        if ($diff->m > 0) return $diff->m . ' month' . ($diff->m > 1 ? 's' : '') . ' ago';
        if ($diff->d > 0) return $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . ' ago';
        if ($diff->h > 0) return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
        if ($diff->i > 0) return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
        
        return 'Just now';
    } catch (Exception $e) {
        return 'Invalid date';
    }
}
?>