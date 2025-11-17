// JavaScript for Courier Management System

document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });

    // Form validation
    const forms = document.querySelectorAll('.needs-validation');
    Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });

    // Parcel tracking form
    const trackForm = document.getElementById('trackForm');
    if (trackForm) {
        trackForm.addEventListener('submit', function(e) {
            const trackingNumber = document.getElementById('trackingNumber').value;
            if (!trackingNumber.trim()) {
                e.preventDefault();
                alert('Please enter a tracking number');
            }
        });
    }

    // Auto-calculate parcel dimensions
    const weightInput = document.getElementById('weight');
    const heightInput = document.getElementById('height');
    const widthInput = document.getElementById('width');
    const lengthInput = document.getElementById('length');
    const calculateBtn = document.getElementById('calculateVolume');

    if (calculateBtn) {
        calculateBtn.addEventListener('click', function() {
            const height = parseFloat(heightInput.value) || 0;
            const width = parseFloat(widthInput.value) || 0;
            const length = parseFloat(lengthInput.value) || 0;
            
            if (height && width && length) {
                const volume = (height * width * length).toFixed(2);
                alert(`Package volume: ${volume} cm³`);
            } else {
                alert('Please enter all dimensions');
            }
        });
    }

    // Status update animations
    const statusBadges = document.querySelectorAll('.status-badge');
    statusBadges.forEach(badge => {
        badge.addEventListener('mouseenter', function() {
            this.style.transform = 'scale(1.1)';
        });
        
        badge.addEventListener('mouseleave', function() {
            this.style.transform = 'scale(1)';
        });
    });

    // Dashboard statistics animation
    const statCards = document.querySelectorAll('.stat-card');
    statCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.boxShadow = '0 10px 25px rgba(0,0,0,0.15)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.boxShadow = '0 0 15px rgba(0,0,0,0.1)';
        });
    });

    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });

    // Print functionality
    const printButtons = document.querySelectorAll('.print-btn');
    printButtons.forEach(button => {
        button.addEventListener('click', function() {
            window.print();
        });
    });

    // Password strength checker
    const passwordInput = document.getElementById('password');
    if (passwordInput) {
        passwordInput.addEventListener('input', function() {
            const strengthBadge = document.getElementById('passwordStrength');
            if (strengthBadge) {
                const password = this.value;
                let strength = 'Weak';
                let color = 'danger';
                
                if (password.length >= 8) {
                    strength = 'Medium';
                    color = 'warning';
                }
                
                if (password.length >= 10 && /[A-Z]/.test(password) && /[0-9]/.test(password) && /[^A-Za-z0-9]/.test(password)) {
                    strength = 'Strong';
                    color = 'success';
                }
                
                strengthBadge.textContent = strength;
                strengthBadge.className = `badge bg-${color}`;
            }
        });
    }
});

// Function to confirm parcel deletion
function confirmDelete(parcelId, trackingNumber) {
    if (confirm(`Are you sure you want to delete parcel ${trackingNumber}? This action cannot be undone.`)) {
        window.location.href = `delete_parcel.php?id=${parcelId}`;
    }
}

// Function to update parcel status
function updateStatus(parcelId, status) {
    if (confirm(`Change status to ${status}?`)) {
        document.getElementById('statusForm').submit();
    }
}

// Function to filter table rows
function filterTable(inputId, tableId) {
    const input = document.getElementById(inputId);
    const filter = input.value.toUpperCase();
    const table = document.getElementById(tableId);
    const rows = table.getElementsByTagName('tr');
    
    for (let i = 1; i < rows.length; i++) {
        const cells = rows[i].getElementsByTagName('td');
        let show = false;
        
        for (let j = 0; j < cells.length; j++) {
            if (cells[j] && cells[j].textContent.toUpperCase().indexOf(filter) > -1) {
                show = true;
                break;
            }
        }
        
        rows[i].style.display = show ? '' : 'none';
    }
}