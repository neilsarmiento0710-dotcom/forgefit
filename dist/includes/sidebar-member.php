<!-- [ Sidebar Menu ] start -->
<nav class="pc-sidebar">
  <div class="navbar-wrapper">
    <!-- Logo Header -->
    <div class="m-header">
      <a href="../member/dashboard.php" class="b-brand text-primary">
        <!-- Note: You may need to ensure the logo image exists or adjust its path -->
        <img src="../assets/images/logo.png" alt="ForgeFit Logo" class="logo-img" />
        <span class="logo-text">ForgeFit</span>
      </a>
    </div>

    <!-- Sidebar Content -->
    <div class="navbar-content">
      <!-- User Profile Card -->
      <div class="card pc-user-card">
        <div class="card-body">
          <div class="d-flex align-items-center">
            <div class="flex-shrink-0">
              <!-- Placeholder avatar for a member -->
              <img src="https://placehold.co/45x45/0096c7/ffffff?text=M" alt="user" class="user-avtar wid-45 rounded-circle" />
            </div>
            <div class="flex-grow-1 ms-3">
              <h6 class="mb-0">John Doe</h6>
              <small>Premium Member</small>
            </div>
          </div>
        </div>
      </div>

      <!-- Navigation Menu -->
      <ul class="pc-navbar">
        <!-- Navigation Section -->
        <li class="pc-item pc-caption">
          <label>Navigation</label>
        </li>

        <li class="pc-item">
          <a href="../member/dashboard.php" class="pc-link">
            <span class="pc-micon">
              <i class="ti ti-dashboard"></i>
            </span>
            <span class="pc-mtext">Dashboard</span>
          </a>
        </li>

        <li class="pc-item">
          <a href="../member/classes.php" class="pc-link">
            <span class="pc-micon">
              <i class="ti ti-users"></i>
            </span>
            <span class="pc-mtext">My Classes</span>
          </a>
        </li>

        <li class="pc-item">
          <a href="../member/trainers.php" class="pc-link">
            <span class="pc-micon">
              <i class="ti ti-user-check"></i>
            </span>
            <span class="pc-mtext">My Trainers</span>
          </a>
        </li>

        <li class="pc-item">
          <a href="../member/membership.php" class="pc-link">
            <span class="pc-micon">
              <i class="ti ti-credit-card"></i>
            </span>
            <span class="pc-mtext">Membership</span>
          </a>
        </li>

        <!-- Others Section -->
        <li class="pc-item pc-caption">
          <label>Others</label>
        </li>

        <li class="pc-item">
          <a href="../member/profile.php" class="pc-link">
            <span class="pc-micon">
              <i class="ti ti-user"></i>
            </span>
            <span class="pc-mtext">My Profile</span>
          </a>
        </li>

        <!-- Settings Section -->
        <li class="pc-item pc-caption">
          <label>Settings</label>
        </li>

        <li class="pc-item">
          <!-- Updated to call the modal function -->
          <a href="#" class="pc-link" onclick="showAboutModal(); return false;">
            <span class="pc-micon">
              <i class="ti ti-info-circle"></i>
            </span>
            <span class="pc-mtext">About</span>
          </a>
        </li>

        <li class="pc-item">
          <!-- Updated to call the confirmation modal function -->
          <a href="#" class="pc-link" onclick="showLogoutConfirm('../logout.php'); return false;">
            <span class="pc-micon">
              <i class="ti ti-logout"></i>
            </span>
            <span class="pc-mtext">Log-Out</span>
          </a>
        </li>

      </ul>
    </div>
  </div>
</nav>
<!-- [ Sidebar Menu ] end -->

<!-- Custom Modal Structure (Invisible by Default) -->
<!-- Styling uses dark theme colors (bg-gray-800) and the primary accent color (#0096c7) -->
<div id="custom-modal-backdrop" class="fixed inset-0 bg-black bg-opacity-50 z-[1050] hidden"></div>
<div id="custom-modal" class="fixed top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 bg-white dark:bg-gray-800 rounded-lg shadow-xl p-6 w-[90%] max-w-md z-[1060] hidden transition-all duration-300">
    <h3 id="modal-title" class="text-xl font-bold mb-4 text-gray-900 dark:text-white"></h3>
    <p id="modal-message" class="mb-6 text-gray-700 dark:text-gray-300 whitespace-pre-line"></p>
    <div id="modal-actions" class="flex justify-end space-x-3">
        <!-- Cancel button, serves as "Close" in informational modals -->
        <button id="modal-cancel" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600 transition duration-150">Cancel</button>
        <!-- Confirm button, uses the primary cyan theme color -->
        <button id="modal-confirm" class="px-4 py-2 bg-[#0096c7] text-white rounded-lg hover:bg-[#007799] transition duration-150">Confirm</button>
    </div>
</div>
<!-- End Custom Modal Structure -->

<script>
// --- Custom Modal Logic (Replacing alert and confirm) ---

const modal = document.getElementById('custom-modal');
const backdrop = document.getElementById('custom-modal-backdrop');
const modalTitle = document.getElementById('modal-title');
const modalMessage = document.getElementById('modal-message');
const modalCancel = document.getElementById('modal-cancel');
const modalConfirm = document.getElementById('modal-confirm');

/**
 * Displays the custom modal with dynamic content and actions.
 * @param {string} title The title of the modal.
 * @param {string} message The message content.
 * @param {boolean} showConfirmButtons Whether to show confirm/cancel buttons (for confirmation).
 * @param {Function} onConfirm Callback function on confirmation.
 */
function showCustomModal(title, message, showConfirmButtons = false, onConfirm = null) {
    modalTitle.textContent = title;
    modalMessage.textContent = message;

    // Reset button states and texts
    modalCancel.classList.remove('hidden');
    modalConfirm.classList.remove('hidden');

    if (showConfirmButtons) {
        // Confirmation mode (e.g., Logout)
        modalCancel.textContent = 'Cancel';
        modalConfirm.textContent = 'Confirm';
        modalCancel.onclick = hideCustomModal;
        modalConfirm.onclick = () => {
            if (onConfirm) onConfirm();
            hideCustomModal();
        };
    } else {
        // Informational mode (e.g., About) - Show only one 'Close' button (using modalCancel)
        modalCancel.textContent = 'Close';
        modalCancel.onclick = hideCustomModal;
        modalConfirm.classList.add('hidden'); // Hide the Confirm button
    }

    backdrop.classList.remove('hidden');
    modal.classList.remove('hidden');
}

function hideCustomModal() {
    modal.classList.add('hidden');
    backdrop.classList.add('hidden');
}

// 1. Replaces the original showAbout()
function showAboutModal() {
    const message = 'Gym Management System\nVersion 1.0 (Member Interface)\n\nDeveloped by: [Your Name]\nContact: [Your Contact]\nEmail: [Your Email]\n\nCopyright © 2025 ForgeFit Solutions.\nAll rights reserved.';
    showCustomModal('About ForgeFit', message, false); // false = informational modal
}

// 2. Replaces the original confirmLogout()
function showLogoutConfirm(logoutUrl) {
    showCustomModal(
        'Confirm Logout', 
        'Are you sure you want to log out of your ForgeFit member session?', 
        true, // true = confirmation modal
        () => {
            // Action to perform on confirmation
            window.location.href = logoutUrl;
        }
    );
}

// Active menu highlight
document.addEventListener('DOMContentLoaded', function() {
    const currentPage = window.location.pathname.split('/').pop();
    const menuLinks = document.querySelectorAll('.pc-link');
    
    menuLinks.forEach(link => {
        const href = link.getAttribute('href');
        
        // Ensure "dashboard.php" is active when the path is just "/" or empty 
        if (href && (href.includes(currentPage) || (currentPage === '' && href.includes('dashboard.php')))) {
            // Directly target the list item
            link.closest('li').classList.add('active');
        }
    });
});
</script>
