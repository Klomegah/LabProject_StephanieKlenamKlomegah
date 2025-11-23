
// Get form and input elements
const form = document.getElementById("form");
const firstname_input = document.getElementById("firstname-input");
const lastname_input = document.getElementById("lastname-input");
const email_input = document.getElementById("email-input");
const password_input = document.getElementById("password-input");
const confirm_password_input = document.getElementById("confirm-password-input");
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
        confirm_password: confirm_password_input.value
    };
    console.log('Payload:', payload);

} else {
    //else we are on the login page
    endpoint='../PHP/login.php';
    payload = {
        email: email_input.value,
        password: password_input.value
    };
}

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

    const result = await response.json();
    

    if((endpoint =='../PHP/signup.php' && result.success) || (endpoint =='../PHP/login.php' && result.success)){
        //Redirect to dashboard on successful signup or login

        window.location.href = 'dashboard.html';

    } else {
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
    error_message.innerText = 'An error occurred. Please try again.';
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


const allInputs = [firstname_input, lastname_input, email_input, password_input, confirm_password_input].filter(input => input !== null);

allInputs.forEach(input => {
    if (input){
        input.addEventListener('input', () => {
            if (input.parentElement.classList.contains('incorrect')){
                input.parentElement.classList.remove('incorrect');
                error_message.innerText = '';
            }
        }
        );
    }

});