<?php
// Shared utility helper functions

/**
 * Sanitize variables for safe HTML display
 */
function sanitize($data) {
    if ($data === null) return '';
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Returns a styled Bootstrap alert notification
 */
function get_alert($message, $type = 'info') {
    $icon = 'info-circle';
    if ($type === 'success') $icon = 'check-circle-fill';
    if ($type === 'danger') $icon = 'exclamation-triangle-fill';
    if ($type === 'warning') $icon = 'exclamation-circle';

    return "
    <div class='alert alert-{$type} alert-dismissible fade show d-flex align-items-center' role='alert'>
        <i class='bi bi-{$icon} me-2 fs-5'></i>
        <div>" . sanitize($message) . "</div>
        <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
    </div>";
}

/**
 * Render rating stars based on numeric rating input (1 to 5)
 */
function get_star_rating($rating) {
    $rating = (int)$rating;
    $stars = '';
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $rating) {
            $stars .= '<i class="bi bi-star-fill text-warning me-1"></i>';
        } else {
            $stars .= '<i class="bi bi-star text-muted me-1"></i>';
        }
    }
    return $stars;
}

/**
 * Render standard certificate design
 */
function get_certificate_html($user_name, $type, $title, $code, $date) {
    return "
    <div class='certificate-wrapper p-5 border border-primary border-5 bg-white text-center shadow-lg position-relative' style='border-style: double !important; max-width: 800px; margin: 0 auto; border-radius: 10px;'>
        <div class='certificate-border' style='position: absolute; top: 10px; left: 10px; right: 10px; bottom: 10px; border: 2px solid var(--accent-color); pointer-events: none;'></div>
        
        <div class='text-primary fs-1 mb-3'><i class='bi bi-journal-bookmark-fill'></i></div>
        <h1 class='font-title text-dark mb-1' style='font-size: 2.2rem;'>Anniyappa Publications</h1>
        <p class='text-muted tracking-wider text-uppercase' style='font-size: 0.8rem; letter-spacing: 3px;'>SB Institute Academic Initiative</p>
        
        <hr class='w-25 mx-auto border-primary border-2 my-4'>
        
        <h2 class='font-title text-primary italic my-4' style='font-family: Georgia, serif;'>Certificate of Achievement</h2>
        <p class='text-dark fs-5 mb-1'>This is proudly presented to</p>
        <h3 class='text-dark fw-bold border-bottom d-inline-block px-4 pb-2 mb-4' style='font-size: 1.8rem; border-color: #cbd5e1 !important;'>{$user_name}</h3>
        
        <p class='text-dark mx-auto my-3' style='max-width: 600px; line-height: 1.6;'>
            for successful completion and active participation in the <strong>{$type}</strong> program on 
            <br>
            <span class='text-primary fw-semibold'>\"{$title}\"</span>
            <br>
            evaluated and conducted by editorial and academic mentors of Anniyappa Publications.
        </p>

        <div class='row mt-5 pt-3 align-items-center justify-content-between'>
            <div class='col-4 text-start'>
                <small class='text-muted d-block'>Date of Issue</small>
                <span class='fw-semibold text-dark'>{$date}</span>
            </div>
            <div class='col-4 text-center'>
                <div class='border border-2 border-warning rounded-circle d-inline-flex align-items-center justify-content-center bg-light shadow-sm' style='width: 70px; height: 70px;'>
                    <i class='bi bi-patch-check-fill text-warning fs-2'></i>
                </div>
            </div>
            <div class='col-4 text-end'>
                <small class='text-muted d-block'>Verification Code</small>
                <span class='fw-semibold text-dark font-monospace' style='font-size: 0.85rem;'>{$code}</span>
            </div>
        </div>
    </div>";
}
?>
