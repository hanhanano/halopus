// Fungsi validasi input wajib diisi
function validateRequired(value, fieldName) {
    if (!value || value.trim() === "") {
        return `${fieldName} harus diisi`;
    }
    return null;
}

// Fungsi validasi format email
function validateEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        return "Format email tidak valid";
    }
    return null;
}

// Fungsi validasi angka beserta batas minimal dan maksimal
function validateNumber(value, fieldName, min = null, max = null) {
    if (isNaN(value) || value === "") {
        return `${fieldName} harus berupa angka`;
    }

    const num = Number.parseFloat(value);
    if (min !== null && num < min) {
        return `${fieldName} minimal ${min}`;
    }
    if (max !== null && num > max) {
        return `${fieldName} maksimal ${max}`;
    }
    return null;
}

// Fungsi validasi tahun terbit
function validateYear(year) {
    const currentYear = new Date().getFullYear();
    const yearNum = Number.parseInt(year);
    if (yearNum < 1900 || yearNum > currentYear) {
        return `Tahun harus antara 1900 dan ${currentYear}`;
    }
    return null;
}

// Setup validasi realtime saat input blur atau diketik
function setupRealTimeValidation() {
    const inputs = document.querySelectorAll(".form-control");

    inputs.forEach((input) => {
        input.addEventListener("blur", function () {
            validateField(this);
        });

        input.addEventListener("input", function () {
            clearFieldError(this);
        });
    });
}

// Fungsi validasi per field saat event terjadi
function validateField(field) {
    const value = field.value.trim();
    const fieldName = field.getAttribute("data-field-name") || field.name;
    let error = null;

    // Validasi input wajib diisi
    if (field.hasAttribute("required")) {
        error = validateRequired(value, fieldName);
        if (error) {
            showFieldError(field, error);
            return false;
        }
    }

    // Validasi khusus berdasarkan tipe input
    if (field.type === "email" && value) {
        error = validateEmail(value);
    } else if (field.type === "number" && value) {
        const min = field.getAttribute("min");
        const max = field.getAttribute("max");
        error = validateNumber(value, fieldName, min, max);
    } else if (field.name === "year_published" && value) {
        error = validateYear(value);
    }

    if (error) {
        showFieldError(field, error);
        return false;
    } else {
        showFieldSuccess(field);
        return true;
    }
}

// Menampilkan pesan error pada field
function showFieldError(field, message) {
    field.classList.add("is-invalid");
    field.classList.remove("is-valid");

    let feedback = field.parentNode.querySelector(".invalid-feedback");
    if (!feedback) {
        feedback = document.createElement("div");
        feedback.className = "invalid-feedback";
        field.parentNode.appendChild(feedback);
    }
    feedback.textContent = message;
}

// Menampilkan status valid pada field
function showFieldSuccess(field) {
    field.classList.add("is-valid");
    field.classList.remove("is-invalid");

    const feedback = field.parentNode.querySelector(".invalid-feedback");
    if (feedback) {
        feedback.remove();
    }
}

// Menghapus pesan error saat input berubah
function clearFieldError(field) {
    field.classList.remove("is-invalid", "is-valid");
    const feedback = field.parentNode.querySelector(".invalid-feedback");
    if (feedback) {
        feedback.remove();
    }
}

// Jalankan setup validasi realtime setelah halaman selesai dimuat
document.addEventListener("DOMContentLoaded", () => {
    setupRealTimeValidation();
});
