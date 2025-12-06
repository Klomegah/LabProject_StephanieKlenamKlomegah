
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
    error_message.innerText = errors.join('. ');
    error_message.style.display = 'block';

    //Auto-hide error message after 5 seconds
    setTimeout(() => {
        error_message.style.display = 'none';
        error_message.innerText = '';
    }, 5000)

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
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify(payload)
    });

    // Check if response is OK
    if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
    }

    // Try to parse JSON response
    let result;
    try {
        const responseText = await response.text();
        console.log('Response text:', responseText);
        result = JSON.parse(responseText);
        console.log('Parsed result:', result);
    } catch (jsonError) {
        console.error('JSON parse error:', jsonError);
        console.error('Response was:', await response.text());
        throw new Error('Invalid response from server. Please try again.');
    }

    console.log('Checking result.success:', result.success);
    console.log('Result role:', result.role);

    if(result.success === true){
        // Hide any error messages before redirecting
        error_message.style.display = 'none';
        error_message.innerText = '';
        
        // Redirect to appropriate dashboard based on role
        const role = result.role || 'student';
        console.log('Redirecting with role:', role);
        
        if (role === 'faculty') {
            console.log('Redirecting to faculty dashboard');
            window.location.href = '../Dashboards/facultydashboard.php';
        } else {
            console.log('Redirecting to student dashboard');
            window.location.href = '../Dashboards/studentdashboard.php';
        } else {
            console.log('Redirecting faculty intern dashboard by default');
            window.location.href = '../Dashboards/facultyinterndashboard.php';
        }

    } else {
        console.log('Showing error:', result.message);
        error_message.innerText = result.message || 'An error occurred. Please try again.';
        error_message.style.display = 'block';
        
        //Auto-hide error message after 5 seconds
        setTimeout(() => {
            error_message.style.display = 'none';
            error_message.innerText = '';
        }, 5000);
    }
} catch (error) {
    console.error('Error:', error);
    error_message.innerText = error.message || 'An error occurred. Please try again.';
    error_message.style.display = 'block';

    //Auto-hide error message after 5 seconds
    setTimeout(() => {
        error_message.style.display = 'none';
        error_message.innerText = '';
    }, 5000);
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