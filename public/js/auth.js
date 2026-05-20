
async function logout() {
    try {
        console.log('Starting logout process...');
        
        // Call the server logout endpoint
        const response = await fetch('/api/auth/logout', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin' // Include cookies/session
        });
        
        if (response.ok) {
            const result = await response.json();
            console.log('Logout successful:', result);
            
            // Clear any client-side storage
            localStorage.clear();
            sessionStorage.clear();
            
            // Clear any cached data
            if (typeof clearUserData === 'function') {
                clearUserData();
            }
            
            // Show success message
            showSuccessMessage('You have been logged out successfully.');
            
            // Redirect to home page with cache busting
            window.location.replace('/');
            
        } else {
            console.error('Logout failed:', response.status);
            // Even if server logout fails, redirect to home
            window.location.replace('/');
        }
        
    } catch (error) {
        console.error('Error during logout:', error);
        // Even if there's an error, redirect to home
        window.location.replace('/');
    }
}

// Function to clear user-specific data
function clearUserData() {
    // Clear any global variables
    if (typeof currentUser !== 'undefined') {
        currentUser = null;
    }
    
    // Clear any cached facility, addon, or equipment data if needed
    facilityData = {};
    addonsData = [];
    equipmentData = [];
    selectedPlan = null;
    selectedAddons = [];
    selectedEquipment = {};
    currentFacility = null;
}

// Prevent back button after logout
function preventBackAfterLogout() {
    // Replace current history entry
    history.replaceState(null, null, '/');
    
    // Listen for popstate (back button)
    window.addEventListener('popstate', function(event) {
        // Check if user is logged out (you can customize this check)
        if (!isUserLoggedIn()) {
            history.replaceState(null, null, '/');
        }
    });
}

// Function to check if user is logged in (customize based on your auth system)
function isUserLoggedIn() {
    // This could check for session, token, or make an API call
    // For now, checking if there's user data in session storage
    return sessionStorage.getItem('user') !== null || localStorage.getItem('user') !== null;
}

// Call this after successful logout
function handleLogoutRedirect() {
    // Clear browser history to prevent back navigation
    if (window.history && window.history.pushState) {
        window.history.replaceState(null, null, '/');
        window.addEventListener('popstate', function() {
            window.history.replaceState(null, null, '/');
        });
    }
    
    // Force redirect to home page
    setTimeout(() => {
        window.location.href = '/';
    }, 100);
}
