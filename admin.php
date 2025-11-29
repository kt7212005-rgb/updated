<?php
session_start();
$isLoggedIn = isset($_SESSION['admin_id']);

// If not logged in, ensure dashboard is hidden
if (!$isLoggedIn) {
    // Force hide dashboard elements
    $showDashboard = false;
    $showPreview = false;
} else {
    $showDashboard = true;
    $showPreview = true;
}
?>
<!DOCTYPE html>
<html><head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Barangay San Antonio 1</title>
    <!-- Tailwind config + CDN -->
    <script src="assets/js/tailwind-config.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Custom styles -->
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body class="bg-brgy-bg min-h-screen">

    <!-- Navbar -->
    <header class="hero-header bg-brgy-primary shadow-xl sticky top-0 z-20">
        <div class="header-content container mx-auto px-4 py-4 flex flex-col sm:flex-row justify-between items-center gap-4">
            <div class="text-center sm:text-left">
                <h1 class="text-2xl md:text-3xl font-extrabold text-white drop-shadow-lg tracking-tight">Admin Dashboard</h1>
                <p class="header-tagline text-sm md:text-base">Barangay San Antonio 1 Management Portal</p>
            </div>
            <nav class="w-full sm:w-auto flex flex-wrap items-center justify-center sm:justify-end gap-3">
                <a href="index.php" class="header-nav-link">Public Portal</a>
                <a href="#" id="logout-btn" class="header-nav-link <?php echo $isLoggedIn ? '' : 'hidden'; ?>">Logout</a>
            </nav>
        </div>
    </header>

    <!-- Main Content Area -->
    <main class="container mx-auto p-4 md:p-8">
        
        <!-- Admin Login Section -->
        <section id="admin-login" class="bg-white p-6 md:p-10 rounded-xl shadow-2xl mb-12">
            <h2 class="text-4xl font-bold text-brgy-primary mb-6 border-b-4 border-brgy-secondary pb-2">Admin Access</h2>
            
            <form id="login-form" class="max-w-md mx-auto p-6 bg-brgy-bg rounded-lg shadow-inner space-y-4 <?php echo $isLoggedIn ? 'hidden' : ''; ?>" onsubmit="attemptLogin(event)">
                <h3 class="text-xl font-semibold text-gray-700">Admin Sign In</h3>
                <p class="text-sm text-gray-600 mb-4">Please log in to access the admin dashboard.</p>
                <div>
                    <label for="admin-username" class="block text-sm font-medium text-gray-700 mb-2">Username</label>
                    <input type="text" id="admin-username" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-brgy-primary focus:border-brgy-primary" placeholder="Enter username" autocomplete="username" required>
                </div>
                <div>
                    <label for="admin-pass" class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                    <input type="password" id="admin-pass" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-brgy-primary focus:border-brgy-primary" placeholder="Enter password" autocomplete="current-password" required>
                </div>
                <button type="submit" class="w-full py-3 bg-white text-brgy-primary font-bold text-lg rounded-lg hover:bg-green-600 hover:text-white hover:shadow-2xl hover:scale-105 active:scale-100 transition-all duration-200 shadow-lg border-2 border-brgy-primary transform hover:scale-[1.02] flex items-center justify-center gap-2">
                    <span></span>
                    <span>Log In</span>
                </button>
                <div class="text-center mt-3">
                    <a href="#" class="text-sm text-brgy-primary hover:text-emerald-700 hover:underline transition-colors duration-200">
                        Forgot Password?
                    </a>
                </div>
                <p id="login-message" class="text-center text-red-500 hidden text-sm mt-2"></p>
            </form>

            <!-- Admin Dashboard (Only visible when logged in) -->
            <div id="admin-dashboard" class="mt-8 <?php echo $isLoggedIn ? '' : 'hidden'; ?>">
                <h3 class="text-3xl font-bold text-brgy-primary mb-6">Budget Update Dashboard</h3>
                
                <!-- Tab Navigation for Admin Functions -->
                <div class="flex border-b border-gray-200 mb-6">
                    <button id="tab-update" onclick="showAdminTab('update')" class="tab-button active px-6 py-3 text-base font-bold rounded-t-lg transition-all duration-200 hover:scale-105 hover:shadow-lg">📊 Update Budget</button>
                    <button id="tab-concerns" onclick="showAdminTab('concerns')" class="tab-button px-4 py-2 text-sm font-medium rounded-t-lg text-gray-600 hover:bg-emerald-600 hover:text-white hover:shadow-lg hover:scale-105 transition-all duration-200">View Concerns (<span id="concern-count">0</span>)</button>
                    <button id="tab-chat" onclick="showAdminTab('chat')" class="tab-button px-4 py-2 text-sm font-medium rounded-t-lg text-gray-600 hover:bg-emerald-600 hover:text-white hover:shadow-lg hover:scale-105 transition-all duration-200">Messages (<span id="chat-count">0</span>)</button>
                    <button id="tab-gallery" onclick="showAdminTab('gallery')" class="tab-button px-4 py-2 text-sm font-medium rounded-t-lg text-gray-600 hover:bg-emerald-600 hover:text-white hover:shadow-lg hover:scale-105 transition-all duration-200">Gallery</button>
                </div>

                <!-- 1. Update Budget Tab -->
                <div id="admin-tab-update" class="space-y-8">
                    <!-- Add New Project Section -->
                    <div class="bg-white border-2 border-brgy-primary rounded-lg shadow-lg p-6 mb-8">
                        <div class="flex items-center gap-3 mb-4">
                            <span class="status-badge status-ongoing">New</span>
                            <h4 class="text-xl font-semibold text-gray-700">Add New Project</h4>
                        </div>
                        <form id="add-project-form" class="space-y-4">
                            <div>
                                <label for="new-category" class="block text-sm font-medium text-gray-700 mb-2">Project/Category Name:</label>
                                <input type="text" id="new-category" required class="w-full p-3 border border-gray-300 rounded-lg focus:ring-brgy-primary focus:border-brgy-primary" placeholder="e.g., Road Infrastructure Project">
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="new-allocated" class="block text-sm font-medium text-gray-700 mb-2">Allocated Amount (₱):</label>
                                    <input type="number" id="new-allocated" required min="0" step="0.01" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-brgy-primary focus:border-brgy-primary" placeholder="0.00">
                                </div>
                                <div>
                                    <label for="new-spent" class="block text-sm font-medium text-gray-700 mb-2">Spent Amount (₱):</label>
                                    <input type="number" id="new-spent" required min="0" step="0.01" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-brgy-primary focus:border-brgy-primary" placeholder="0.00">
                                </div>
                            </div>
                            <div>
                                <label for="new-status" class="block text-sm font-medium text-gray-700 mb-2">Status:</label>
                                <select id="new-status" required class="w-full p-3 border border-gray-300 rounded-lg focus:ring-brgy-primary focus:border-brgy-primary">
                                    <option value="Initial">Initial</option>
                                    <option value="Ongoing">Ongoing</option>
                                    <option value="Pending">Pending</option>
                                    <option value="Completed">Completed</option>
                                </select>
                            </div>
                            <div>
                                <label for="new-progress" class="block text-sm font-medium text-gray-700 mb-2">Project Progress/Process (Optional):</label>
                                <textarea id="new-progress" rows="3" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-brgy-primary focus:border-brgy-primary" placeholder="Describe the project progress, activities, or milestones..."></textarea>
                            </div>
                            <div class="flex justify-start">
                                <button type="submit" class="px-6 py-2 bg-brgy-secondary text-brgy-primary font-bold text-base rounded-lg hover:bg-yellow-400 hover:shadow-xl hover:scale-105 active:scale-100 transition-all duration-200 shadow-md border-2 border-yellow-500">
                                     Add New Project
                                </button>
                            </div>
                            <p id="add-project-message" class="text-center font-medium hidden"></p>
                        </form>
                    </div>

                    <h4 class="text-xl font-semibold mb-4 text-gray-700">Select Item to Update</h4>
                    <select id="budget-item-select" class="w-full p-3 border border-gray-300 rounded-lg mb-6" onchange="loadItemForEdit()">
                        <option value="">-- Select Category --</option>
                        <!-- Options populated by JS -->
                    </select>

                    <form id="update-form" class="p-6 border border-gray-200 rounded-lg bg-gray-50 shadow-md">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Allocated Amount (₱):</label>
                                <input type="number" id="edit-allocated" required="" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-brgy-primary focus:border-brgy-primary">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Spent Amount (₱):</label>
                                <input type="number" id="edit-spent" required="" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-brgy-primary focus:border-brgy-primary">
                            </div>
                        </div>
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Status:</label>
                            <select id="edit-status" required="" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-brgy-primary focus:border-brgy-primary">
                                <option value="Initial">Initial</option>
                                <option value="Ongoing">Ongoing</option>
                                <option value="Completed">Completed</option>
                                <option value="Pending">Pending</option>
                            </select>
                        </div>
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Project Progress/Process:</label>
                            <textarea id="edit-progress" rows="4" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-brgy-primary focus:border-brgy-primary" placeholder="Describe the current progress, activities, milestones, or updates for this project..."></textarea>
                            <p class="text-xs text-gray-500 mt-1">Provide detailed information about the project's current status, activities, and progress.</p>
                        </div>
                        <div class="flex justify-start">
                            <button type="button" onclick="saveBudgetUpdate()" class="px-6 py-2 bg-brgy-secondary text-brgy-primary font-bold text-base rounded-lg hover:bg-yellow-400 hover:shadow-xl hover:scale-105 active:scale-100 transition-all duration-200 shadow-md border-2 border-yellow-500">
                                 Apply Changes
                            </button>
                        </div>
                    </form>
                    <p id="update-message" class="mt-4 text-center font-medium text-green-600 hidden"></p>

                    <div class="bg-white border border-gray-200 rounded-lg shadow-md p-6">
                        <div class="flex items-center gap-3 mb-4">
                            <span class="status-badge status-ongoing">New</span>
                            <h4 class="text-xl font-semibold text-gray-700">Publish Announcement</h4>
                        </div>
                        <form id="admin-post-form" class="space-y-4" enctype="multipart/form-data">
                            <div>
                                <label for="post-title" class="block text-sm font-medium text-gray-700 mb-1">Title</label>
                                <input id="post-title" type="text" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-brgy-primary focus:border-brgy-primary" placeholder="e.g., Road Rehabilitation Update">
                            </div>
                            <div>
                                <label for="post-body" class="block text-sm font-medium text-gray-700 mb-1">Message</label>
                                <textarea id="post-body" rows="4" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-brgy-primary focus:border-brgy-primary" placeholder="Share the latest activities, advisories, or announcements..."></textarea>
                            </div>
                            <div>
                                <label for="post-image" class="block text-sm font-medium text-gray-700 mb-1">Image (optional)</label>
                                <input id="post-image" type="file" accept="image/*" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-brgy-primary focus:border-brgy-primary">
                                <p class="text-xs text-gray-500 mt-1">Supported formats: JPG, PNG, GIF, WebP (Max 5MB)</p>
                            </div>
                            <div class="flex justify-start">
                                <button type="submit" class="px-6 py-2 bg-brgy-secondary text-brgy-primary font-bold text-base rounded-lg hover:bg-yellow-400 hover:shadow-xl hover:scale-105 active:scale-100 transition-all duration-200 shadow-md border-2 border-yellow-500">
                                     Publish Update
                                </button>
                            </div>
                            <p id="post-message" class="text-center font-medium hidden"></p>
                        </form>
                    </div>
                </div>
                
                <!-- 2. View Concerns Tab -->
                <div id="admin-tab-concerns" class="hidden">
                    <div class="flex justify-between items-center mb-4">
                        <h4 class="text-xl font-semibold text-gray-700">Citizen Concerns &amp; Recommendations</h4>
                        <div class="flex gap-2">
                            <select id="concern-status-filter" onchange="filterConcerns()" class="px-3 py-1 border border-gray-300 rounded-lg text-sm focus:ring-brgy-primary focus:border-brgy-primary">
                                <option value="">All Status</option>
                                <option value="Pending">Pending</option>
                                <option value="In Progress">In Progress</option>
                                <option value="Resolved">Resolved</option>
                            </select>
                            <button onclick="refreshConcerns()" class="px-3 py-1 bg-brgy-primary text-white text-sm rounded-lg hover:bg-emerald-700">
                                🔄 Refresh
                            </button>
                        </div>
                    </div>
                    <div id="concerns-list" class="space-y-4">
                        <p class="text-gray-500" id="no-concerns">No new concerns or recommendations.</p>
                        <!-- Concerns will be added here -->
                    </div>
                </div>

                <!-- 3. Chat Messages Tab -->
                <div id="admin-tab-chat" class="hidden">
                    <h4 class="text-xl font-semibold mb-4 text-gray-700">User Messages &amp; Conversations</h4>
                    <div id="chat-list" class="space-y-4 mb-6">
                        <p class="text-gray-500" id="no-chats">No messages yet.</p>
                        <!-- Chat conversations will be added here -->
                    </div>
                    
                    <!-- Selected Conversation View -->
                    <div id="chat-conversation" class="hidden bg-white border border-gray-200 rounded-lg shadow-md p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h5 class="text-lg font-semibold text-gray-700" id="conversation-title">Conversation</h5>
                            <button onclick="closeConversation()" class="text-gray-500 hover:text-gray-700">×</button>
                        </div>
                        <div id="conversation-messages" class="h-96 overflow-y-auto p-4 bg-gray-50 rounded-lg mb-4 space-y-2">
                            <!-- Messages will be loaded here -->
                        </div>
                        <div class="flex gap-2">
                            <input type="text" id="admin-reply-input" placeholder="Type your reply..." class="flex-grow p-3 border border-gray-300 rounded-lg focus:ring-brgy-primary focus:border-brgy-primary">
                            <button onclick="sendAdminReply()" class="px-6 py-3 bg-brgy-primary text-white font-bold rounded-lg hover:bg-emerald-700 transition duration-150">Send</button>
                        </div>
                        <input type="hidden" id="current-conversation-id">
                    </div>
                </div>

                <!-- 4. Gallery Management Tab -->
                <div id="admin-tab-gallery" class="hidden">
                    <h4 class="text-xl font-semibold mb-4 text-gray-700">Manage Live Project Gallery</h4>
                    
                    <!-- Add New Image Form -->
                    <div class="bg-white border border-gray-200 rounded-lg shadow-md p-6 mb-6">
                        <h5 class="text-lg font-semibold text-gray-700 mb-4">Add New Image</h5>
                        <form id="gallery-add-form" class="space-y-4" enctype="multipart/form-data">
                            <div>
                                <label for="gallery-image-file" class="block text-sm font-medium text-gray-700 mb-2">Upload Image</label>
                                <input type="file" id="gallery-image-file" accept="image/*" required class="w-full p-3 border border-gray-300 rounded-lg focus:ring-brgy-primary focus:border-brgy-primary">
                                <p class="text-xs text-gray-500 mt-1">Supported formats: JPG, PNG, GIF, WebP</p>
                            </div>
                            <div>
                                <label for="gallery-alt-text" class="block text-sm font-medium text-gray-700 mb-2">Alt Text (Description)</label>
                                <input type="text" id="gallery-alt-text" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-brgy-primary focus:border-brgy-primary" placeholder="Description of the image">
                            </div>
                            <div>
                                <label for="gallery-display-order" class="block text-sm font-medium text-gray-700 mb-2">Display Order</label>
                                <input type="number" id="gallery-display-order" value="0" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-brgy-primary focus:border-brgy-primary" placeholder="0">
                                <p class="text-xs text-gray-500 mt-1">Lower numbers appear first. Use 0 for default ordering.</p>
                            </div>
                            <button type="submit" class="w-full py-3 bg-brgy-secondary text-brgy-primary font-bold rounded-lg hover:bg-yellow-400 transition duration-150 shadow-md">Upload Image</button>
                            <p id="gallery-add-message" class="text-center font-medium hidden"></p>
                        </form>
                    </div>

                    <!-- Gallery Images List -->
                    <div class="bg-white border border-gray-200 rounded-lg shadow-md p-6">
                        <h5 class="text-lg font-semibold text-gray-700 mb-4">Current Gallery Images</h5>
                        <div id="gallery-images-list" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <p class="text-gray-500">Loading gallery images...</p>
                        </div>
                    </div>
                </div>

            </div>
        </section>

        <!-- Live Projects Preview Section (Only visible when logged in) -->
        <section id="live-projects-preview" class="bg-white p-6 md:p-10 rounded-xl shadow-2xl mb-12 <?php echo $isLoggedIn ? '' : 'hidden'; ?>">
            <h2 class="text-4xl font-bold text-brgy-primary mb-6 border-b-4 border-brgy-secondary pb-2">Live Projects Preview</h2>
            <p class="text-gray-600 mb-8">This is how the gallery appears to users on the public portal.</p>
            
            <div class="bg-gray-900 text-white rounded-xl shadow-2xl p-6 md:p-10">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
                    <div>
                        <h3 class="text-3xl font-bold">Barangay in Action</h3>
                        <p class="text-gray-300">Ongoing infrastructure and maintenance efforts around San Antonio 1.</p>
                    </div>
                    <span class="text-sm uppercase tracking-widest text-gray-400">Live Project Highlights</span>
                </div>
                <div class="gallery-marquee">
                    <div class="gallery-track" id="admin-gallery-preview">
                        <!-- Gallery images will be loaded here -->
                    </div>
                </div>
                <p class="mt-4 text-xs text-gray-400">Tip: Hover to pause the scroll.</p>
            </div>
        </section>

    </main>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white p-6 mt-8">
        <div class="container mx-auto text-center">
            <p>© 2025 Barangay San Antonio 1. Budget Transparency Portal, San Pablo City.</p>
        </div>
    </footer>

    <!-- Main application logic -->
    <script defer src="assets/js/admin.js"></script>

</body></html>

