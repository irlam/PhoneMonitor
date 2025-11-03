/**
 * PhoneMonitor JavaScript
 */

// Auto-refresh dashboard every 60 seconds
if (window.location.pathname === '/dashboard.php' || window.location.pathname === '/devices.php') {
    let refreshTimer = null;
    
    function startAutoRefresh() {
        refreshTimer = setTimeout(function() {
            window.location.reload();
        }, 60000); // 60 seconds
    }
    
    function stopAutoRefresh() {
        if (refreshTimer) {
            clearTimeout(refreshTimer);
        }
    }
    
    // Start auto-refresh
    startAutoRefresh();
    
    // Stop refresh when user is interacting
    document.addEventListener('click', function() {
        stopAutoRefresh();
        startAutoRefresh();
    });
}

// Add confirmation dialogs for destructive actions
document.addEventListener('DOMContentLoaded', function() {
    const dangerButtons = document.querySelectorAll('.btn-danger');
    
    dangerButtons.forEach(function(button) {
        if (!button.closest('form[onsubmit]')) {
            button.addEventListener('click', function(e) {
                if (!confirm('Are you sure you want to perform this action?')) {
                    e.preventDefault();
                    return false;
                }
            });
        }
    });
});

// Simple form validation helper
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return true;
    
    const requiredFields = form.querySelectorAll('[required]');
    let isValid = true;
    
    requiredFields.forEach(function(field) {
        if (!field.value.trim()) {
            field.style.borderColor = 'var(--danger-color)';
            isValid = false;
        } else {
            field.style.borderColor = 'var(--border-color)';
        }
    });
    
    return isValid;
}
