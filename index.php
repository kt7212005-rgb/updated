<?php
require_once __DIR__ . '/api/db.php';

// Preload gallery images for faster rendering
$preloadedGalleryImages = [];
$tableCheck = $conn->query("SHOW TABLES LIKE 'gallery_images'");
if ($tableCheck->num_rows > 0) {
    $sql = 'SELECT id, image_url, alt_text, display_order FROM gallery_images ORDER BY display_order ASC, created_at ASC';
    $result = $conn->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $preloadedGalleryImages[] = $row;
        }
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html><head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Budget Transparency Portal - Barangay San Antonio 1</title>
    <!-- Tailwind config + CDN -->
    <script src="assets/js/tailwind-config.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Custom styles -->
    <link rel="stylesheet" href="assets/css/styles.css">
    <script>
        // Preload gallery images from PHP
        window.preloadedGalleryImages = <?php echo json_encode($preloadedGalleryImages); ?>;
    </script>
</head>
<body class="bg-brgy-bg min-h-screen">

    <!-- Navbar -->
    <header class="hero-header bg-brgy-primary shadow-xl sticky top-0 z-20">
        <div class="header-content container mx-auto px-4 py-4 flex flex-col sm:flex-row justify-between items-center gap-4">
            <div class="text-center sm:text-left">
                <h1 class="text-2xl md:text-3xl font-extrabold text-white drop-shadow-lg tracking-tight">Barangay San Antonio 1</h1>
                <p class="header-tagline text-sm md:text-base">Budget Transparency Portal</p>
            </div>
            <nav class="w-full sm:w-auto flex flex-wrap items-center justify-center sm:justify-end gap-3">
                <a href="admin.php" class="header-nav-link">Admin Portal</a>
            </nav>
        </div>
        <!-- Navigation Tabs -->
        <div class="container mx-auto px-4 pb-2">
            <nav class="flex flex-wrap gap-2 justify-center sm:justify-start">
                <button onclick="showTab('home')" id="nav-home" class="nav-tab tab-button active px-6 py-2 rounded-t-lg text-white font-semibold transition-all duration-200">Home</button>
                <button onclick="showTab('budget')" id="nav-budget" class="nav-tab tab-button px-6 py-2 rounded-t-lg text-white font-semibold transition-all duration-200">Budget Transparency</button>
                <button onclick="showTab('announcement')" id="nav-announcement" class="nav-tab tab-button px-6 py-2 rounded-t-lg text-white font-semibold transition-all duration-200">Announcement</button>
                <button onclick="showTab('barangay')" id="nav-barangay" class="nav-tab tab-button px-6 py-2 rounded-t-lg text-white font-semibold transition-all duration-200">Barangay in Action</button>
            </nav>
        </div>
    </header>

    <!-- Main Content Area -->
    <main class="container mx-auto p-4 md:p-8">
        
        <!-- Home Section -->
        <section id="content-home" class="content-section">
            <!-- Dashboard Statistics Header -->
            <div class="dashboard-header relative bg-gradient-to-br from-blue-900 via-blue-800 to-red-800 rounded-xl shadow-2xl mb-8 overflow-hidden" style="min-height: 300px;">
                <div class="absolute inset-0 bg-black opacity-40"></div>
                <div class="relative z-10 p-8 md:p-12 text-white">
                    <h2 class="text-3xl md:text-4xl font-bold mb-8 text-center">(2016-2025)</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 max-w-4xl mx-auto">
                        <div class="text-center">
                            <div id="dashboard-total-investment" class="text-5xl md:text-6xl font-extrabold mb-2">₱6.359T</div>
                            <div class="text-xl md:text-2xl font-semibold">Total Investment</div>
                        </div>
                        <div class="text-center">
                            <div id="dashboard-total-contracts" class="text-5xl md:text-6xl font-extrabold mb-2">247,198</div>
                            <div class="text-xl md:text-2xl font-semibold">Total Contracts</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Status Cards -->
            <div id="status-cards-container" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
                <!-- Status cards will be populated by JavaScript -->
            </div>

            <!-- Search Bar -->
            <div class="mb-8">
                <div class="relative max-w-4xl mx-auto">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                    <input type="text" id="contract-search" placeholder="Search by contract name, location, or contractor..." class="w-full pl-12 pr-4 py-4 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-brgy-primary focus:border-brgy-primary text-lg">
                </div>
            </div>

            <!-- Contracts Table -->
            <div class="bg-white p-6 md:p-10 rounded-xl shadow-2xl">
                <div class="overflow-x-auto">
                    <table id="contractsTable" class="w-full">
                        <thead class="bg-brgy-primary text-white">
                            <tr>
                                <th class="px-6 py-4 text-left text-sm font-bold uppercase tracking-wider">CONTRACT DESCRIPTION</th>
                                <th class="px-6 py-4 text-left text-sm font-bold uppercase tracking-wider">IMPLEMENTING OFFICE</th>
                                <th class="px-6 py-4 text-left text-sm font-bold uppercase tracking-wider">CONTRACTOR</th>
                                <th class="px-6 py-4 text-left text-sm font-bold uppercase tracking-wider">COST</th>
                                <th class="px-6 py-4 text-left text-sm font-bold uppercase tracking-wider">ACCOMPLISHMENT</th>
                                <th class="px-6 py-4 text-left text-sm font-bold uppercase tracking-wider">COMPLETION DATE</th>
                                <th class="px-6 py-4 text-left text-sm font-bold uppercase tracking-wider">REPORT</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <!-- Table rows will be populated by JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <!-- Budget Transparency Section -->
        <section id="content-budget" class="content-section hidden">
            <div class="bg-white p-6 md:p-10 rounded-xl shadow-2xl mb-12">
                <h2 class="text-4xl font-bold text-brgy-primary mb-6 border-b-4 border-brgy-secondary pb-2">Budget Transparency</h2>
                <p class="text-gray-600 mb-8">View the annual budget allocations, expenditures, and project status for Barangay San Antonio 1, San Pablo City.</p>
                
                <!-- Summary Cards -->
                <div id="summary-cards" class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <!-- Summary cards will be populated by JavaScript -->
                </div>

                <!-- Budget Table -->
                <div class="table-card bg-white rounded-xl shadow-lg overflow-hidden">
                    <div class="overflow-x-auto">
                        <table id="budgetTable" class="w-full">
                            <thead class="bg-brgy-primary text-white">
                                <tr>
                                    <th class="px-6 py-4 text-left text-sm font-bold uppercase tracking-wider">Project/Category</th>
                                    <th class="px-6 py-4 text-left text-sm font-bold uppercase tracking-wider">Allocated (₱)</th>
                                    <th class="px-6 py-4 text-left text-sm font-bold uppercase tracking-wider">Spent (₱)</th>
                                    <th class="px-6 py-4 text-left text-sm font-bold uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-4 text-left text-sm font-bold uppercase tracking-wider">Progress/Updates</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <!-- Table rows will be populated by JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>

        <!-- Announcements Section -->
        <section id="content-announcement" class="content-section hidden">
            <div class="bg-white p-6 md:p-10 rounded-xl shadow-2xl mb-12">
                <h2 class="text-4xl font-bold text-brgy-primary mb-6 border-b-4 border-brgy-secondary pb-2">Latest Announcements</h2>
                <p class="text-gray-600 mb-8">Stay updated with the latest news, updates, and announcements from Barangay San Antonio 1.</p>
                
                <div id="public-posts" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Posts will be populated by JavaScript -->
                </div>
            </div>
        </section>

        <!-- Barangay in Action Section -->
        <section id="content-barangay" class="content-section hidden">
            <div class="bg-white p-6 md:p-10 rounded-xl shadow-2xl mb-12">
                <div class="bg-gray-900 text-white rounded-xl shadow-2xl p-6 md:p-10">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
                        <div>
                            <h3 class="text-3xl font-bold">Barangay in Action</h3>
                            <p class="text-gray-300">Ongoing infrastructure and maintenance efforts around San Antonio 1.</p>
                        </div>
                        <span class="text-sm uppercase tracking-widest text-gray-400">Live Project Highlights</span>
                    </div>
                    <div class="gallery-marquee">
                        <div class="gallery-track" id="gallery-track">
                            <!-- Gallery images will be loaded here -->
                        </div>
                    </div>
                    <p class="mt-4 text-xs text-gray-400">Tip: Hover to pause the scroll.</p>
                </div>
            </div>
        </section>

    </main>

    <!-- Chatbot Section (Fixed Position) -->
    <div id="chatbot-container" class="hidden fixed bottom-4 right-4 w-80 md:w-96 bg-white rounded-xl shadow-2xl border-2 border-brgy-primary z-50 flex flex-col max-h-[600px]">
        <div class="bg-brgy-primary text-white p-4 rounded-t-xl flex justify-between items-center">
            <h3 class="font-bold text-lg">💬 Chat with Us</h3>
            <button onclick="toggleChatbot()" class="text-black hover:text-white bg-white hover:bg-gray-700 w-8 h-8 rounded-full flex items-center justify-center text-2xl font-bold transition-colors duration-200">&times;</button>
        </div>
        
        <!-- Message Type Selection -->
        <div id="message-type-selector" class="p-4 border-b border-gray-200 bg-white">
            <p class="text-sm font-medium text-gray-700 mb-2">How would you like to contact us?</p>
            <div class="grid grid-cols-2 gap-2">
                <button onclick="selectMessageType('concern')" class="px-3 py-2 bg-orange-500 text-white text-sm rounded-lg hover:bg-orange-600 transition duration-150">
                    📋 Submit Concern
                </button>
                <button onclick="selectMessageType('message')" class="px-3 py-2 bg-blue-500 text-white text-sm rounded-lg hover:bg-blue-600 transition duration-150">
                    💬 Live Chat
                </button>
            </div>
        </div>
        
        <!-- Concern Form -->
        <div id="concern-form" class="hidden flex-1 overflow-y-auto p-4 bg-gray-50 flex flex-col">
            <div class="flex-1">
                <div class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Message *</label>
                        <textarea id="concern-message" rows="4" placeholder="Type your message here..." class="w-full p-2 border border-gray-300 rounded-lg focus:ring-brgy-primary focus:border-brgy-primary"></textarea>
                    </div>
                    <button onclick="submitConcern()" class="w-full py-2 bg-orange-500 text-white font-bold rounded-lg hover:bg-orange-600 transition duration-150">
                        Send Message
                    </button>
                </div>
            </div>
            <div class="mt-4 pt-4 border-t border-gray-200">
                <button onclick="resetChatbot()" class="w-full py-2 bg-gray-300 text-gray-700 font-bold rounded-lg hover:bg-gray-400 transition duration-150">
                    Close
                </button>
            </div>
        </div>
        
        <!-- Chat Messages -->
        <div id="chatbot-messages" class="hidden flex-1 overflow-y-auto p-4 bg-gray-50 space-y-2">
            <div class="message-box bot">
                Hello! 👋 Welcome to Barangay San Antonio 1's Budget Transparency Portal. How can I help you today? You can ask about the budget, submit concerns, or get information about our services.
            </div>
        </div>
        
        <!-- Chat Input -->
        <div id="chat-input-container" class="hidden p-4 border-t border-gray-200 bg-white rounded-b-xl">
            <div class="flex gap-2">
                <input type="text" id="user-input" placeholder="Type your message..." class="flex-1 p-3 border border-gray-300 rounded-lg focus:ring-brgy-primary focus:border-brgy-primary" autocomplete="off">
                <button onclick="handleUserMessage()" class="px-6 py-3 bg-brgy-primary text-white font-bold rounded-lg hover:bg-emerald-700 transition duration-150">Send</button>
            </div>
            <button onclick="resetChatbot()" class="w-full mt-2 py-2 bg-gray-300 text-gray-700 font-bold rounded-lg hover:bg-gray-400 transition duration-150">
                Back to Options
            </button>
        </div>
    </div>

    <!-- Chatbot Toggle Button -->
    <button onclick="toggleChatbot()" class="fixed bottom-4 right-4 bg-black text-white p-4 rounded-full shadow-2xl hover:bg-gray-800 hover:scale-110 transition-all duration-200 z-40 flex items-center justify-center w-16 h-16">
        <span class="text-2xl">💬</span>
    </button>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white p-6 mt-8">
        <div class="container mx-auto text-center">
            <p>© 2025 Barangay San Antonio 1. Budget Transparency Portal, San Pablo City.</p>
        </div>
    </footer>

    <!-- Main application logic -->
    <script defer src="assets/js/app.js"></script>

</body></html>

