// public/js/admin.js
document.addEventListener('DOMContentLoaded', function() {
    console.log('MediLink Admin initialisé');
    
    // Initialiser les tooltips Bootstrap
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Initialiser les popovers
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
    
    // Mettre à jour l'heure
    updateTime();
    setInterval(updateTime, 1000);
    
    // Gestion des confirmations
    initConfirmations();
    
    // Gestion de la sidebar mobile
    initMobileSidebar();
});

// Mettre à jour l'heure
function updateTime() {
    const now = new Date();
    const timeString = now.toLocaleTimeString('fr-FR', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    });
    const dateString = now.toLocaleDateString('fr-FR', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
    
    const timeElements = document.querySelectorAll('.time-display');
    timeElements.forEach(element => {
        element.textContent = `${dateString} • ${timeString}`;
    });
}

// Sidebar mobile (hamburger + overlay)
function initMobileSidebar() {
    const sidebar = document.querySelector('.sidebar');
    const toggleBtn = document.querySelector('.topbar-menu-toggle, [onclick="toggleSidebar()"]');
    
    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', function() {
            sidebar.classList.toggle('active');
            document.body.classList.toggle('sidebar-open', sidebar.classList.contains('active'));
        });
    }
    
    document.addEventListener('click', function(event) {
        if (window.innerWidth <= 992 && sidebar && sidebar.classList.contains('active')) {
            if (!sidebar.contains(event.target) && !event.target.closest('.topbar-menu-toggle')) {
                sidebar.classList.remove('active');
                document.body.classList.remove('sidebar-open');
            }
        }
    });
}

// Fonction globale pour toggle sidebar
function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    if (sidebar) {
        sidebar.classList.toggle('active');
    }
}

// Confirmations
function initConfirmations() {
    // Confirmations de suppression
    const deleteButtons = document.querySelectorAll('[data-confirm-delete]');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            if (!confirm('Êtes-vous sûr de vouloir supprimer cet élément ?')) {
                e.preventDefault();
            }
        });
    });
    
    // Confirmations de validation
    const validateButtons = document.querySelectorAll('[data-confirm-validate]');
    validateButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            if (!confirm('Valider ce don ? Il sera visible sur le site public.')) {
                e.preventDefault();
            }
        });
    });
    
    // Confirmations de rejet
    const rejectButtons = document.querySelectorAll('[data-confirm-reject]');
    rejectButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            if (!confirm('Rejeter ce don ? Cette action est définitive.')) {
                e.preventDefault();
            }
        });
    });
}

// Rafraîchir les données
function refreshData() {
    location.reload();
}

// Fonction pour afficher les statistiques
function showStats() {
    // Implémentation future pour les graphiques
    console.log('Affichage des statistiques');
}