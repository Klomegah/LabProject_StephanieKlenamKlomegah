const logout = async () => {
    try {
        const response = await fetch('../PHP/logout.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            }
        });

        const result = await response.json();

        if (result.logout === true) {
            // Display success notification and redirect to login page
            await Swal.fire({
                icon: 'success',
                title: 'Logged Out',
                text: 'You have been successfully logged out.',
                timer: 2000,
                showConfirmButton: false
            });

            // Redirect to login page
            window.location.href = 'login.html';
        } else {
            // Handle logout failure
            Swal.fire({
                icon: 'error',
                title: 'Logout Failed',
                text : "An error occurred while logging out. Please try again."
            });
        }
    } catch (error) {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Network Error',
            text: "Could not connect to the server to log out. Please try again later."
        });
    }
};

// Trigger the logout function when the logout button is clicked
document.addEventListener('DOMContentLoaded', () => {
    const logoutButtons = document.querySelectorAll('.logout-button');
    
    logoutButtons.forEach(button => {
        button.addEventListener('click', (e) => {
            e.preventDefault(); // Stop default button action
            logout(); // Call the logout function
        });
    });
});
