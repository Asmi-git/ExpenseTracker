console.log("JS Loaded");
document.addEventListener("DOMContentLoaded", function () {

    const inputs = document.querySelectorAll("input");

    inputs.forEach(function (input) {

        input.addEventListener("input", function () {

            let msg = document.getElementById("msg");

            if (msg) {
                msg.style.display = "none";
            }

        });

    });

});
const form = document.getElementById("register-form");
form.addEventListener("submit", function (event) {

    let password = document.getElementById("pswd").value;
    let confirmPassword = document.getElementById("confirmpswd").value;

    document.getElementById("passwordError").innerText = "";
    document.getElementById("confirmPasswordError").innerText = "";

    let isValid = true;

    let passwordPattern = /^(?=.*\d)(?=.*[^A-Za-z0-9]).+$/;

    if (password.trim() === "") {
        document.getElementById("passwordError").innerText = "Password is required";
        isValid = false;
    }

    else if (password.length < 8) {
        document.getElementById("passwordError").innerText = "Password must be atleast 8 characters";
        isValid = false;
    }

    else if (!passwordPattern.test(password)) {
        document.getElementById("passwordError").innerText = "Password must contain a number and special character";
        isValid = false;
    }

    else if (confirmPassword.trim() === "") {
        document.getElementById("confirmPasswordError").innerText = "Confirm password is required";
        isValid = false;
    }
    else if (password !== confirmPassword) {
        document.getElementById("confirmPasswordError").innerText = "Passwords do not match";
        isValid = false;
    }

    if (!isValid) {
        event.preventDefault();
    }

})
console.log("Form submitted");