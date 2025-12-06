
// Get form and input elements
const form = document.getElementById("form");
const firstname_input = document.getElementById("firstname-input");
const lastname_input = document.getElementById("lastname-input");
const email_input = document.getElementById("email-input");
const password_input = document.getElementById("password-input");
const confirm_password_input = document.getElementById("confirm-password-input");
const role_input = document.getElementById("role-input");
const error_message = document.getElementById("error-message");

// Form submit event listener
form.addEventListener('submit', async (e) => {
  e.preventDefault(); // prevent form submission for testing purposes

  //Determine if we are on sign up or login page based on presence of firstname input
  let errors = [];
    if (firstname_input){
        //if we have a first name input, we are on the sign up page
        errors = getSignUpErrors(firstname_input.value,
                                 lastname_input.value, 
                                 email_input.value, 
                                 password_input.value,
                                 confirm_password_input.value);

    }

    else{
        //else we are on the login page
        errors = getLoginErrors(email_input.value, password_input.value )
    }

if (errors.length > 0) {
    e.preventDefault();
    Swal.fire({
        title: "Validation Error",
        text: errors.join('. '),
        icon: "warning"
    });
    return; //stop further execution
}

// Async function to handle form submission
let endpoint = '';
let payload={};

if (firstname_input){
    //if we have a first name input, we are on the sign up page

    endpoint='../PHP/signup.php';
    payload = {
        firstname: firstname_input.value,
        lastname: lastname_input.value,
        email: email_input.value,
        password: password_input.value,
        confirm_password: confirm_password_input.value,
        role: role_input ? role_input.value : 'student'
    };
    
    //comment this out later
    console.log('Payload:', payload);

} else {
    //else we are on the login page
    endpoint='../PHP/login.php';
    payload = {
        email: email_input.value,
        password: password_input.value
    };
}

//comment this out later
console.log('Payload:', payload);

// Send fetch request with JSON payload

try {
    const response = await fetch(endpoint, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(payload)
    });

    // Check if response is OK, if not try to get error message
    if (!response.ok) {
        let errorText = '';
        try {
            const errorResult = await response.json();
            errorText = errorResult.message || `Server error (${response.status})`;
        } catch (e) {
            errorText = `Server error (${response.status}). Please check your connection.`;
        }
        Swal.fire({
            title: "Connection Error",
            text: errorText,
            icon: "error"
        });
        return;
    }

    let result;
    try {
        result = await response.json();
    } catch (jsonError) {
        // If JSON parsing fails, show a user-friendly error
        Swal.fire({
            title: "Error",
            text: "Invalid response from server. Please try again.",
            icon: "error"
        });
        return;
    }
    
    if((endpoint =='../PHP/signup.php' && result.success) || (endpoint =='../PHP/login.php' && result.success)){
        // Show success message
        if (endpoint == '../PHP/signup.php') {
            Swal.fire({
                title: "Success!",
                text: "Your account has been created successfully!",
                icon: "success",
                timer: 2000,
                showConfirmButton: false
            });
        } else {
            Swal.fire({
                title: "Login Successful!",
                text: `Welcome back!`,
                icon: "success",
                timer: 1500,
                showConfirmButton: false
            });
        }
        
        // Redirect to appropriate dashboard based on role after a short delay
        setTimeout(() => {
            const role = result.role || 'student';
            if (role === 'faculty') {
                // Check if this is a faculty intern (only set during signup)
                if (result.is_faculty_intern === true) {
                    window.location.href = '../Dashboards/facultyinterndashboard.php';
                } else {
                    window.location.href = '../Dashboards/facultydashboard.php';
                }
            } else {
                window.location.href = '../Dashboards/studentdashboard.php';
            }
        }, endpoint == '../PHP/signup.php' ? 2000 : 1500);
    } else {
        Swal.fire({
            title: "Error",
            text: result.message || 'An error occurred. Please try again.',
            icon: "error"
        });
    }
    
} catch (error) {
    console.error('Error:', error);
    // Check if it's a network error
    if (error instanceof TypeError && error.message.includes('fetch')) {
        Swal.fire({
            title: "Connection Failed",
            text: "Cannot connect to server. Please check your internet connection and try again.",
            icon: "error"
        });
    } else {
        Swal.fire({
            title: "Error",
            text: "An unexpected error occurred. Please try again.",
            icon: "error"
        });
    }
}

});

// Validation functions
function getSignUpErrors(firstname, lastname, email, password, confirm_password){
    let errors = [];
    //validate first name
    if (!firstname || firstname.trim() == ''){
        errors.push("First name is required");
        firstname_input.parentElement.classList.add('incorrect');
    } else if (firstname.trim().length < 2){
        errors.push("First name must be at least 2 characters long");
        firstname_input.parentElement.classList.add('incorrect');
    }

    //validate last name
    if (!lastname || lastname.trim() == ''){
        errors.push("Last name is required");
        lastname_input.parentElement.classList.add('incorrect');
    } else if (lastname.trim().length < 2){
        errors.push("Last name must be at least 2 characters long");
        lastname_input.parentElement.classList.add('incorrect');
    }

    //validate email
    if (!email || email.trim() == ''){
        errors.push("Email is required");
        email_input.parentElement.classList.add('incorrect');
    }else {
         const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailPattern.test(email)) {
            errors.push("Please enter a valid email address");
            email_input.parentElement.classList.add('incorrect');
        }
    }
    

    //validate password
    if (!password || password.trim() == ''){
        errors.push("Password is required");
        password_input.parentElement.classList.add('incorrect');
    }else if (password.length < 8){
        errors.push("Password must be at least 8 characters long");
        password_input.parentElement.classList.add('incorrect');
    }

    //validate confirm password
    if (!confirm_password || confirm_password == ''){
        errors.push("Confirm password is required");
        confirm_password_input.parentElement.classList.add('incorrect');
    }else if (password !== confirm_password){
        errors.push("Passwords do not match");
        confirm_password_input.parentElement.classList.add('incorrect');
    }

    //validate role
    if (role_input && (!role_input.value || role_input.value.trim() == '')){
        errors.push("Please select a role");
        role_input.parentElement.classList.add('incorrect');
    }

    return errors;
}

function getLoginErrors(email, password){
    let errors = [];
    
       //validate email
    if (!email || email.trim() == ''){
        errors.push("Email is required");
        email_input.parentElement.classList.add('incorrect');
    }else {
         const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailPattern.test(email)) {
            errors.push("Please enter a valid email address");
            email_input.parentElement.classList.add('incorrect');
        }
    }

    //validate password
    if (!password || password.trim() == ''){
        errors.push("Password is required");
        password_input.parentElement.classList.add('incorrect');
    }else if (password.length < 8){
        errors.push("Password must be at least 8 characters long");
        password_input.parentElement.classList.add('incorrect');
    }

    return errors;
}

const allInputs = [firstname_input, lastname_input, email_input, password_input, confirm_password_input, role_input].filter(input => input !== null);

allInputs.forEach(input => {
    if (input){
        const eventType = input.tagName === 'SELECT' ? 'change' : 'input';
        input.addEventListener(eventType, () => {
            if (input.parentElement.classList.contains('incorrect')){
                input.parentElement.classList.remove('incorrect');
                error_message.innerText = '';
            }
        }
        );
    }

});
