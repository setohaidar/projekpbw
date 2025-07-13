// validasi_email_password.js

document.addEventListener('DOMContentLoaded', function() {
    
    // Ambil elemen dari form registrasi
    const registerForm = document.getElementById('registerForm');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const passwordError = document.getElementById('passwordError');
    
    // Fungsi untuk validasi format email menggunakan Regex
    function validateEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(String(email).toLowerCase());
    }

    // Fungsi untuk validasi password
    function validatePassword(password) {
        // Aturan 1: Minimal 8 karakter
        if (password.length < 8) {
            return "Password harus minimal 8 karakter.";
        }

        // Aturan 2: Harus mengandung huruf (a-z, A-Z)
        const hasLetter = /[a-zA-Z]/.test(password);

        // Aturan 3: Harus mengandung angka (0-9)
        const hasNumber = /\d/.test(password);

        if (!hasLetter || !hasNumber) {
            return "Password harus merupakan kombinasi huruf dan angka.";
        }

        // Jika semua aturan terpenuhi
        return ""; // Mengembalikan string kosong berarti valid
    }


    if (registerForm) {
        registerForm.addEventListener('submit', function(event) {
            
            let formIsValid = true;
            
            // 1. Validasi Email
            if (!validateEmail(emailInput.value)) {
                alert('Format email yang Anda masukkan tidak valid.');
                formIsValid = false;
            }

            // 2. Validasi Password
            const passwordValidationMessage = validatePassword(passwordInput.value);
            if (passwordValidationMessage) { // Jika ada pesan error
                passwordError.textContent = passwordValidationMessage; // Tampilkan pesan error
                formIsValid = false; // Tandai form tidak valid
            } else {
                passwordError.textContent = ""; // Hapus pesan error jika sudah valid
            }

            // Jika salah satu validasi gagal, hentikan pengiriman form
            if (!formIsValid) {
                event.preventDefault(); 
            }
        });
    }
});