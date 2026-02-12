// public/js/front.js
document.addEventListener('DOMContentLoaded', function() {
    console.log('MediLink Frontend initialisé');
    
    // Initialiser les tooltips Bootstrap
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Validation des formulaires
    initFormValidation();
    
    // Gestion des unités dans le formulaire
    initUnitManagement();
});

function initFormValidation() {
    const forms = document.querySelectorAll('.needs-validation');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            
            form.classList.add('was-validated');
        }, false);
    });
}

function initUnitManagement() {
    const uniteSelect = document.getElementById('don_front_unite');
    const unitIndicator = document.getElementById('unitIndicator');
    
    if (uniteSelect && unitIndicator) {
        // Mettre à jour l'indicateur quand l'unité change
        uniteSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption) {
                unitIndicator.textContent = selectedOption.text;
            }
        });
        
        // Initialiser
        const selectedOption = uniteSelect.options[uniteSelect.selectedIndex];
        if (selectedOption) {
            unitIndicator.textContent = selectedOption.text;
        }
    }
}

// Fonction pour afficher les messages
function showMessage(message, type = 'success') {
    const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
    const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
    
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert ${alertClass} alert-dismissible fade show m-0 rounded-0`;
    alertDiv.role = 'alert';
    alertDiv.innerHTML = `
        <div class="container">
            <i class="fas ${icon} me-2"></i> ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    document.body.prepend(alertDiv);
    
    // Auto-dismiss after 5 seconds
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}

// Fonction pour confirmer les actions
function confirmAction(message) {
    return confirm(message);
}