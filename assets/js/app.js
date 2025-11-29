/* global fetch */

const fallbackBudgetData = [
    { id: 1, category: 'Personnel Services (Salaries)', allocated: 3200000, spent: 2800000, status: 'Ongoing' },
    { id: 2, category: 'Maintenance and Operating Expenses (MOOE)', allocated: 4500000, spent: 2100000, status: 'Ongoing' },
    { id: 3, category: '20% Development Fund (Infrastructure)', allocated: 2000000, spent: 1500000, status: 'Completed' },
    { id: 4, category: 'Calamity Fund (5%)', allocated: 600000, spent: 0, status: 'Initial' },
    { id: 5, category: 'SK Fund (Youth Programs)', allocated: 800000, spent: 300000, status: 'Pending' },
    { id: 6, category: 'Gender and Development (GAD)', allocated: 900000, spent: 450000, status: 'Ongoing' },
];

const fallbackPosts = [
    {
        id: 1,
        title: 'Road Rehabilitation Update',
        body: 'Nightly works continue along the main thoroughfare to minimize traffic during peak hours. Expect partial lane closures.',
        image_url: null,
        created_at: new Date().toISOString(),
    },
    {
        id: 2,
        title: 'Health Center Expansion',
        body: 'The barangay health center is adding two consultation rooms and a dedicated vaccination bay. Construction kicks off next week.',
        image_url: null,
        created_at: new Date().toISOString(),
    },
];

let budgetData = [...fallbackBudgetData];
let posts = [...fallbackPosts];
let concernsList = [];
let selectedItemId = null;
let isAdminAuthenticated = false;
let contractsData = [];
let filteredContracts = [];

async function loadBudgetData() {
    try {
        const response = await fetch('api/get_budget.php', { cache: 'no-store' });
        if (!response.ok) throw new Error('Unable to reach the budget API.');
        const data = await response.json();
        if (Array.isArray(data) && data.length > 0) {
            budgetData = data.map((item, index) => ({
                id: Number(item.id ?? index + 1),
                category: item.category,
                allocated: Number(item.allocated),
                spent: Number(item.spent),
                status: item.status,
                project_progress: item.project_progress || null,
            }));
        } else {
            console.warn('Budget API returned no rows. Using fallback data.');
            budgetData = [...fallbackBudgetData];
        }
    } catch (error) {
        console.warn('Budget API unavailable, reverting to fallback seed.', error);
        budgetData = [...fallbackBudgetData];
    }
}

async function loadPosts() {
    try {
        // Always fetch fresh data with no cache
        const response = await fetch('api/get_posts.php', { 
            cache: 'no-store',
            headers: {
                'Cache-Control': 'no-cache',
                'Pragma': 'no-cache'
            }
        });
        if (!response.ok) throw new Error('Unable to reach the posts API.');
        const data = await response.json();
        posts = Array.isArray(data) ? data : [];
        // Sort by latest first (newest at the top)
        posts.sort((a, b) => {
            const dateA = new Date(a.created_at || a.createdAt || 0);
            const dateB = new Date(b.created_at || b.createdAt || 0);
            return dateB - dateA; // Descending order (newest first)
        });
        console.log('Loaded', posts.length, 'announcements from database');
    } catch (error) {
        console.warn('Posts API unavailable, reverting to fallback announcements.', error);
        posts = [...fallbackPosts];
    }
    // Note: renderPublicPosts() is called separately when tab is clicked
}

function formatCurrency(amount) {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
        minimumFractionDigits: 2,
    }).format(amount);
}

function renderBudget() {
    const tableBody = document.querySelector('#budgetTable tbody');
    const summaryCards = document.getElementById('summary-cards');
    if (!tableBody || !summaryCards) return;

    tableBody.innerHTML = '';
    summaryCards.innerHTML = '';

    let totalBudget = 0;
    let totalSpent = 0;

    budgetData.forEach((item) => {
        totalBudget += item.allocated;
        totalSpent += item.spent;

        const remaining = item.allocated - item.spent;
        const percentageSpent = item.allocated === 0 ? 0 : (item.spent / item.allocated) * 100;
        const sanitizedProgress = Math.max(0, Math.min(percentageSpent, 100));

        const row = document.createElement('tr');
        row.className = 'allocation-row';

        const categoryCell = document.createElement('td');
        categoryCell.className = 'px-6 py-4 align-top';
        categoryCell.innerHTML = `
            <div class="allocation-category">
                <span class="allocation-category-title">${item.category}</span>
                <span class="allocation-category-remaining">Remaining: ${formatCurrency(remaining)}</span>
                <div class="allocation-progress-track mt-2">
                    <div class="allocation-progress-bar" style="--progress: ${sanitizedProgress}%"></div>
                </div>
            </div>
        `;

        const allocatedCell = document.createElement('td');
        allocatedCell.className = 'px-6 py-4 text-sm text-gray-700 font-semibold';
        allocatedCell.textContent = formatCurrency(item.allocated);

        const spentCell = document.createElement('td');
        spentCell.className = 'px-6 py-4 text-sm text-gray-700 font-semibold';
        spentCell.innerHTML = `
            <div>
                <p>${formatCurrency(item.spent)}</p>
                <p class="text-xs text-gray-500">${sanitizedProgress.toFixed(1)}% spent</p>
            </div>
        `;

        const statusCell = document.createElement('td');
        statusCell.className = 'px-6 py-4';
        statusCell.innerHTML = `<span class="status-badge status-${item.status.toLowerCase()}">${item.status}</span>`;

        const progressCell = document.createElement('td');
        progressCell.className = 'px-6 py-4';
        if (item.project_progress && item.project_progress.trim()) {
            const progressId = `progress-${item.id}`;
            const button = document.createElement('button');
            button.textContent = 'View Details';
            button.className = 'text-brgy-primary hover:text-brgy-secondary text-sm font-semibold underline';
            button.onclick = () => toggleProgress(progressId);
            
            const detailsDiv = document.createElement('div');
            detailsDiv.id = progressId;
            detailsDiv.className = 'hidden mt-2 p-3 bg-gray-50 rounded-lg border border-gray-200';
            const detailsText = document.createElement('p');
            detailsText.className = 'text-sm text-gray-700 whitespace-pre-wrap';
            detailsText.textContent = item.project_progress;
            
            detailsDiv.appendChild(detailsText);
            progressCell.appendChild(button);
            progressCell.appendChild(detailsDiv);
        } else {
            const noProgress = document.createElement('span');
            noProgress.className = 'text-gray-400 text-sm';
            noProgress.textContent = 'No updates yet';
            progressCell.appendChild(noProgress);
        }

        row.append(categoryCell, allocatedCell, spentCell, statusCell, progressCell);
        tableBody.appendChild(row);
    });

    const totalRemaining = totalBudget - totalSpent;
    const summaryData = [
        { title: 'Total Annual Budget', value: formatCurrency(totalBudget), accent: 'from-blue-500 to-blue-700' },
        { title: 'Total Funds Spent', value: formatCurrency(totalSpent), accent: 'from-rose-500 to-rose-700' },
        { title: 'Remaining Balance', value: formatCurrency(totalRemaining), accent: 'from-emerald-500 to-emerald-700' },
    ];

    summaryData.forEach((data) => {
        const card = document.createElement('div');
        card.className = 'summary-card bg-white p-6 rounded-xl shadow-lg border border-gray-100';
        card.innerHTML = `
            <p class="text-sm uppercase tracking-wide text-gray-400">${data.title}</p>
            <p class="mt-2 text-3xl font-extrabold bg-gradient-to-r ${data.accent} text-transparent bg-clip-text">${data.value}</p>
        `;
        summaryCards.appendChild(card);
    });
}

function renderPublicPosts() {
    const postsContainer = document.getElementById('public-posts');
    if (!postsContainer) {
        console.warn('Posts container not found');
        return;
    }

    postsContainer.innerHTML = '';

    if (!posts || posts.length === 0) {
        postsContainer.innerHTML = '<div class="col-span-2 text-center py-12"><p class="text-gray-500 text-lg">No announcements yet. Check back soon!</p><p class="text-gray-400 text-sm mt-2">The barangay will post updates here.</p></div>';
        return;
    }

    // Sort by latest first (newest at the top)
    const sortedPosts = [...posts].sort((a, b) => {
        const dateA = new Date(a.created_at || a.createdAt || 0);
        const dateB = new Date(b.created_at || b.createdAt || 0);
        return dateB - dateA; // Descending order (newest first)
    });

    console.log('Rendering', sortedPosts.length, 'announcements');

    sortedPosts.forEach((post, index) => {
        const card = document.createElement('article');
        card.className = 'post-card bg-white p-5 rounded-xl shadow-md border border-gray-100 flex flex-col gap-3 hover:shadow-lg transition-shadow';
        
        const postDate = post.created_at || post.createdAt || new Date().toISOString();
        const dateObj = new Date(postDate);
        const formattedDate = dateObj.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
        const formattedTime = dateObj.toLocaleTimeString('en-US', {
            hour: '2-digit',
            minute: '2-digit'
        });
        
        // Handle image URL - check if it needs uploads/posts/ prefix
        let imageUrl = post.image_url || '';
        if (imageUrl && !imageUrl.startsWith('http://') && !imageUrl.startsWith('https://') && !imageUrl.startsWith('/')) {
            if (!imageUrl.includes('uploads/posts/') && !imageUrl.includes('uploads\\posts\\')) {
                imageUrl = 'uploads/posts/' + imageUrl;
            }
        }
        
        // Show "NEW" badge for recent posts (within last 7 days)
        const daysAgo = (Date.now() - dateObj.getTime()) / (1000 * 60 * 60 * 24);
        const isNew = daysAgo <= 7;
        
        card.innerHTML = `
            <div>
                <div class="flex items-center justify-between mb-1">
                    <p class="text-xs uppercase tracking-wider text-gray-400">${formattedDate} at ${formattedTime}</p>
                    ${isNew ? '<span class="bg-red-500 text-white text-xs px-2 py-1 rounded-full font-semibold">NEW</span>' : ''}
                </div>
                <h4 class="text-xl font-bold text-brgy-primary mt-1">${post.title || 'Announcement'}</h4>
            </div>
            <p class="text-gray-700 leading-relaxed">${post.body || post.message || ''}</p>
            ${imageUrl ? `<img src="${imageUrl}" alt="${post.title || 'Announcement'}" class="rounded-lg mt-2 w-full h-auto object-cover max-h-64" onerror="this.style.display='none'">` : ''}
        `;
        postsContainer.appendChild(card);
    });
}

async function attemptLogin(event) {
    if (event) event.preventDefault();

    const usernameInput = document.getElementById('admin-username');
    const passwordInput = document.getElementById('admin-pass');
    const loginMessage = document.getElementById('login-message');
    loginMessage.classList.add('hidden');

    const username = usernameInput.value.trim();
    const password = passwordInput.value;

    if (!username || !password) {
        loginMessage.textContent = 'Please enter both username and password.';
        loginMessage.classList.remove('hidden');
        return;
    }

    try {
        const response = await fetch('api/login.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify({ username, password }),
        });

        if (!response.ok) throw new Error('Invalid credentials. Please try again.');

        const result = await response.json();
        if (result.success) {
            isAdminAuthenticated = true;
            document.getElementById('login-form').classList.add('hidden');
            document.getElementById('admin-dashboard').classList.remove('hidden');
            const navLink = document.getElementById('nav-admin-link');
            navLink.textContent = 'Admin Logged In';
            navLink.classList.remove('bg-brgy-secondary');
            navLink.classList.add('bg-green-500');
            enablePostForm(true);

            populateAdminSelect();
            renderConcerns();
        }
    } catch (error) {
        loginMessage.textContent = error.message;
        loginMessage.classList.remove('hidden');
    }
}

function enablePostForm(enabled) {
    const postForm = document.getElementById('admin-post-form');
    if (!postForm) return;
    if (enabled) {
        postForm.classList.remove('opacity-50', 'pointer-events-none');
    } else {
        postForm.classList.add('opacity-50', 'pointer-events-none');
    }
}

function populateAdminSelect() {
    const select = document.getElementById('budget-item-select');
    if (!select) return;
    select.innerHTML = '<option value="">-- Select Category --</option>';

    budgetData.forEach((item) => {
        const option = document.createElement('option');
        option.value = item.id;
        option.textContent = item.category;
        select.appendChild(option);
    });
}

function loadItemForEdit() {
    const select = document.getElementById('budget-item-select');
    selectedItemId = Number(select.value);
    const updateMessage = document.getElementById('update-message');
    updateMessage.classList.add('hidden');
    updateMessage.textContent = '';

    if (selectedItemId) {
        const item = budgetData.find((d) => d.id === selectedItemId);
        document.getElementById('edit-allocated').value = item.allocated;
        document.getElementById('edit-spent').value = item.spent;
        document.getElementById('edit-status').value = item.status;
    } else {
        document.getElementById('edit-allocated').value = '';
        document.getElementById('edit-spent').value = '';
        document.getElementById('edit-status').value = 'Initial';
    }
}

async function saveBudgetUpdate() {
    const updateMessage = document.getElementById('update-message');

    if (!selectedItemId) {
        updateMessage.textContent = 'Please select a category to update.';
        updateMessage.classList.remove('hidden', 'text-green-600');
        updateMessage.classList.add('text-red-600');
        return;
    }

    const allocated = parseFloat(document.getElementById('edit-allocated').value);
    const spent = parseFloat(document.getElementById('edit-spent').value);
    const status = document.getElementById('edit-status').value;

    if (Number.isNaN(allocated) || Number.isNaN(spent) || allocated < spent) {
        updateMessage.textContent = 'Invalid amounts. Allocated must be greater than or equal to Spent.';
        updateMessage.classList.remove('hidden', 'text-green-600');
        updateMessage.classList.add('text-red-600');
        return;
    }

    const payload = { id: selectedItemId, allocated, spent, status };
    let updateSucceeded = false;

    try {
        const response = await fetch('api/update_budget.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify(payload),
        });

        if (!response.ok) throw new Error('Unable to reach the server.');

        const result = await response.json();
        if (!result.success) throw new Error(result.message || 'Update failed.');

        updateSucceeded = true;
        if (result.updatedItem) {
            const { id, allocated: alloc, spent: sp, status: stat } = result.updatedItem;
            const localIndex = budgetData.findIndex((entry) => entry.id === Number(id));
            if (localIndex > -1) {
                budgetData[localIndex] = {
                    ...budgetData[localIndex],
                    allocated: Number(alloc),
                    spent: Number(sp),
                    status: stat,
                };
            }
        }
    } catch (error) {
        console.error('Server update failed:', error);
        updateMessage.textContent = `Update failed: ${error.message}. Please ensure the PHP API is running.`;
        updateMessage.classList.remove('hidden', 'text-green-600');
        updateMessage.classList.add('text-red-600');
        return;
    }

    if (updateSucceeded) {
        renderBudget();
        populateAdminSelect();

        updateMessage.textContent = 'Budget category successfully updated!';
        updateMessage.classList.remove('hidden', 'text-red-600');
        updateMessage.classList.add('text-green-600');

        document.getElementById('budget-item-select').value = '';
        loadItemForEdit();
        selectedItemId = null;
    }
}

async function submitAdminPost(event) {
    event.preventDefault();
    if (!isAdminAuthenticated) {
        alert('Please log in as admin to post updates.');
        return;
    }

    const titleInput = document.getElementById('post-title');
    const bodyInput = document.getElementById('post-body');
    const imageInput = document.getElementById('post-image');
    const formMessage = document.getElementById('post-message');
    formMessage.classList.add('hidden');

    const payload = {
        title: titleInput.value.trim(),
        body: bodyInput.value.trim(),
        imageUrl: imageInput.value.trim() || null,
    };

    if (!payload.title || !payload.body) {
        formMessage.textContent = 'Title and message are required.';
        formMessage.classList.remove('hidden', 'text-green-600');
        formMessage.classList.add('text-red-600');
        return;
    }

    try {
        const response = await fetch('api/create_post.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify(payload),
        });

        if (!response.ok) {
            const errorPayload = await response.json();
            throw new Error(errorPayload.message || 'Unable to publish post.');
        }

        const { post } = await response.json();
        posts.unshift(post);
        renderPublicPosts();

        formMessage.textContent = 'Announcement published!';
        formMessage.classList.remove('hidden', 'text-red-600');
        formMessage.classList.add('text-green-600');
        document.getElementById('admin-post-form').reset();
    } catch (error) {
        formMessage.textContent = error.message;
        formMessage.classList.remove('hidden', 'text-green-600');
        formMessage.classList.add('text-red-600');
    }
}

function showAdminTab(tabName) {
    ['update', 'concerns'].forEach((tab) => {
        document.getElementById(`admin-tab-${tab}`).classList.add('hidden');
        document.getElementById(`tab-${tab}`).classList.remove('active');
    });

    document.getElementById(`admin-tab-${tabName}`).classList.remove('hidden');
    document.getElementById(`tab-${tabName}`).classList.add('active');
}

function renderConcerns() {
    const listContainer = document.getElementById('concerns-list');
    const counter = document.getElementById('concern-count');
    listContainer.innerHTML = '';
    counter.textContent = concernsList.length;

    if (concernsList.length === 0) {
        listContainer.innerHTML = '<p class="text-gray-500" id="no-concerns">No new concerns or recommendations.</p>';
        return;
    }

    concernsList.forEach((concern, index) => {
        const concernDiv = document.createElement('div');
        concernDiv.className = 'p-4 bg-white border border-gray-100 rounded-lg shadow-sm';
        concernDiv.innerHTML = `
            <p class="text-sm font-bold text-brgy-primary">Concern #${index + 1} (${concern.type}):</p>
            <p class="text-gray-700 mt-1">${concern.message}</p>
            <p class="text-xs text-gray-400 mt-2">Received: ${new Date(concern.timestamp).toLocaleTimeString()}</p>
        `;
        listContainer.appendChild(concernDiv);
    });
}

function toggleChatbot() {
    const container = document.getElementById('chatbot-container');
    const wasHidden = container.classList.contains('hidden');
    container.classList.toggle('hidden');
    
    if (!container.classList.contains('hidden')) {
        // Show message type selector when opening chatbot
        resetChatbot();
    }
}

// Select message type (concern or message)
function selectMessageType(type) {
    const selector = document.getElementById('message-type-selector');
    const concernForm = document.getElementById('concern-form');
    const chatMessages = document.getElementById('chatbot-messages');
    const chatInput = document.getElementById('chat-input-container');
    
    // Hide selector
    selector.classList.add('hidden');
    
    if (type === 'concern') {
        concernForm.classList.remove('hidden');
        chatMessages.classList.add('hidden');
        chatInput.classList.add('hidden');
    } else if (type === 'message') {
        concernForm.classList.add('hidden');
        chatMessages.classList.remove('hidden');
        chatInput.classList.remove('hidden');
        
        // Focus on input and load existing messages
        const input = document.getElementById('user-input');
        input.focus();
        loadedMessageIds.clear();
        const conversationId = getConversationId();
        loadUserMessages(conversationId);
    }
}

// Reset chatbot to initial state
function resetChatbot() {
    const selector = document.getElementById('message-type-selector');
    const concernForm = document.getElementById('concern-form');
    const chatMessages = document.getElementById('chatbot-messages');
    const chatInput = document.getElementById('chat-input-container');
    
    selector.classList.remove('hidden');
    concernForm.classList.add('hidden');
    chatMessages.classList.add('hidden');
    chatInput.classList.add('hidden');
    
    // Clear concern form
    document.getElementById('concern-name').value = '';
    document.getElementById('concern-email').value = '';
    document.getElementById('concern-type').value = '';
    document.getElementById('concern-message').value = '';
}

// Submit concern
async function submitConcern() {
    const name = document.getElementById('concern-name').value.trim();
    const email = document.getElementById('concern-email').value.trim();
    const concernType = document.getElementById('concern-type').value;
    const message = document.getElementById('concern-message').value.trim();
    
    if (!name || !concernType || !message) {
        alert('Please fill in all required fields.');
        return;
    }
    
    try {
        const response = await fetch('api/submit_concern.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                name: name,
                email: email,
                concern_type: concernType,
                message: message
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('Your concern has been submitted successfully! We will review it and respond accordingly.');
            resetChatbot();
            toggleChatbot(); // Close chatbot after submission
        } else {
            alert('Error: ' + result.message);
        }
    } catch (error) {
        console.error('Error submitting concern:', error);
        alert('Failed to submit concern. Please try again.');
    }
}

// Get or create conversation ID
function getConversationId() {
    let conversationId = localStorage.getItem('chat_conversation_id');
    if (!conversationId) {
        conversationId = 'user_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
        localStorage.setItem('chat_conversation_id', conversationId);
    }
    return conversationId;
}

async function handleUserMessage() {
    const inputField = document.getElementById('user-input');
    const userMessage = inputField.value.trim();

    if (!userMessage) return;

    const conversationId = getConversationId();
    
    // Display user message immediately
    addMessage(userMessage, 'user');
    inputField.value = '';
    
    // Save message to database
    try {
        const response = await fetch('api/send_message.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                conversation_id: conversationId,
                sender_type: 'user',
                message: userMessage
            })
        });
        
        if (response.ok) {
            const result = await response.json();
            if (result.success && result.message) {
                loadedMessageIds.add(result.message.id);
            }
        }
    } catch (error) {
        console.error('Failed to save message:', error);
    }
    
    // Show bot response and also save it
    simulateBotResponse(userMessage, conversationId);
    
    // Load any new messages from admin
    setTimeout(() => loadUserMessages(conversationId), 1000);
}


async function simulateBotResponse(userMessage, conversationId) {
    const lowerCaseMsg = userMessage.toLowerCase();
    let botResponse = "Thank you for your message! An admin will review and respond to your inquiry shortly. Your message has been saved.";

    if (lowerCaseMsg.includes('total budget') || lowerCaseMsg.includes('allocated')) {
        const total = budgetData.reduce((sum, item) => sum + item.allocated, 0);
        botResponse = `The Total Annual Budget for Barangay San Antonio 1 is currently ${formatCurrency(total)}. You can view the full breakdown in the table above. An admin can provide more details if needed.`;
    } else if (lowerCaseMsg.includes('admin') || lowerCaseMsg.includes('update')) {
        botResponse = 'The Admin Login is for authorized barangay officials only. Once logged in, officials can update budget figures and broadcast announcements.';
    } else if (lowerCaseMsg.includes('concern') || lowerCaseMsg.includes('recommendation') || lowerCaseMsg.includes('suggest')) {
        concernsList.push({
            type: 'Concern/Recommendation',
            message: userMessage,
            timestamp: Date.now(),
        });
        renderConcerns();
        botResponse = 'Your concern has been formally logged and will be reviewed by the admin staff. Thank you for participating!';
    } else if (lowerCaseMsg.includes('contact') || lowerCaseMsg.includes('office')) {
        botResponse = 'You can contact the Barangay Hall at (049) 555-1234 or visit the office during business hours (M-F, 8am-5pm).';
    }

    setTimeout(() => {
        addMessage(botResponse, 'bot');
    }, 700);
}

let loadedMessageIds = new Set();

async function loadUserMessages(conversationId) {
    try {
        const response = await fetch(`api/get_messages.php?conversation_id=${encodeURIComponent(conversationId)}&sender_type=user`, {
            cache: 'no-store'
        });
        
        if (!response.ok) return;
        
        const result = await response.json();
        if (result.success && result.messages) {
            const messagesContainer = document.getElementById('chatbot-messages');
            if (!messagesContainer) return;
            
            // If this is the first load, clear and add all messages
            if (loadedMessageIds.size === 0) {
                const greeting = messagesContainer.querySelector('.message-box.bot:first-child');
                messagesContainer.innerHTML = '';
                if (greeting) {
                    messagesContainer.appendChild(greeting);
                }
                
                // Add all messages from database
                result.messages.forEach((msg) => {
                    const sender = msg.sender_type === 'user' ? 'user' : 'bot';
                    addMessage(msg.message, sender, false);
                    loadedMessageIds.add(msg.id);
                });
            } else {
                // Only add new messages
                result.messages.forEach((msg) => {
                    if (!loadedMessageIds.has(msg.id)) {
                        const sender = msg.sender_type === 'user' ? 'user' : 'bot';
                        addMessage(msg.message, sender, true);
                        loadedMessageIds.add(msg.id);
                    }
                });
            }
        }
    } catch (error) {
        console.error('Failed to load messages:', error);
    }
}

function addMessage(text, sender, scroll = true) {
    const chatbotMessages = document.getElementById('chatbot-messages');
    const messageDiv = document.createElement('div');
    messageDiv.classList.add('message-box', sender);
    messageDiv.textContent = text;
    chatbotMessages.appendChild(messageDiv);
    if (scroll) {
        chatbotMessages.scrollTop = chatbotMessages.scrollHeight;
    }
}

// Fallback images if database is empty or fails to load
const fallbackGalleryImages = [
    { image_url: 'image 1.jpg', alt_text: 'Construction crew working on foundation' },
    { image_url: 'image 2.jpeg', alt_text: 'Road paving team smoothing concrete' },
    { image_url: 'image 3.jpg', alt_text: 'Construction crew working on foundation' },
    { image_url: 'image 4.jpg', alt_text: 'Road paving team smoothing concrete' },
    { image_url: 'jayve.png', alt_text: 'Barangay project image' }
];

async function loadGalleryImages() {
    try {
        // Always fetch fresh data with no cache to show latest project images
        const response = await fetch('api/get_gallery.php', { 
            cache: 'no-store',
            headers: {
                'Cache-Control': 'no-cache',
                'Pragma': 'no-cache'
            }
        });
        
        if (!response.ok) {
            throw new Error('Unable to load gallery images.');
        }
        
        const images = await response.json();
        
        // Check if response is an array
        if (Array.isArray(images)) {
            // Use database images if available, otherwise use fallback
            if (images.length > 0) {
                console.log('Loading', images.length, 'gallery images from database');
                renderGalleryImages(images);
                // Update preloaded images
                window.preloadedGalleryImages = images;
            } else {
                // If database is empty, show fallback images
                console.log('No images in database, using fallback');
                renderGalleryImages(fallbackGalleryImages);
            }
        } else {
            // Invalid response format
            console.warn('Invalid response format, using fallback');
            renderGalleryImages(fallbackGalleryImages);
        }
    } catch (error) {
        console.error('Failed to load gallery:', error);
        // Fallback to existing images if API fails
        if (window.preloadedGalleryImages && window.preloadedGalleryImages.length > 0) {
            renderGalleryImages(window.preloadedGalleryImages);
        } else {
            renderGalleryImages(fallbackGalleryImages);
        }
    }
}

function renderGalleryImages(images) {
    // Try to find gallery track - it might be in a hidden section
    let galleryTrack = document.getElementById('gallery-track');
    if (!galleryTrack) {
        // If not found, it might be because the section is hidden
        // Try to find it anyway
        const barangaySection = document.getElementById('content-barangay');
        if (barangaySection) {
            galleryTrack = barangaySection.querySelector('#gallery-track');
        }
    }
    
    if (!galleryTrack) {
        console.warn('Gallery track element not found - section might be hidden');
        // Store images to render later when section is visible
        window.pendingGalleryImages = images;
        return;
    }
    
    // Clear any pending images
    window.pendingGalleryImages = null;
    
    // Check if images are already rendered (from PHP)
    const existingImages = galleryTrack.querySelectorAll('img.gallery-photo');
    if (existingImages.length > 0 && images && images.length > 0) {
        // Compare if we need to update
        const currentSrcs = Array.from(existingImages).map(img => img.src.split('/').pop());
        const newSrcs = images.map(img => {
            const url = img.image_url || img.url || '';
            return url.split('/').pop();
        });
        
        // Only update if images have changed
        if (JSON.stringify(currentSrcs.slice(0, images.length).sort()) === 
            JSON.stringify(newSrcs.sort())) {
            console.log('Gallery images unchanged, skipping re-render');
            return;
        }
    }
    
    galleryTrack.innerHTML = '';
    
    if (!images || images.length === 0) {
        // If no images at all, show fallback
        console.log('No images to render, using fallback');
        renderGalleryImages(fallbackGalleryImages);
        return;
    }
    
    console.log('Rendering', images.length, 'gallery images');
    
    // Add images twice for seamless scrolling
    [...images, ...images].forEach((image, index) => {
        const img = document.createElement('img');
        // Ensure image URL is properly formatted
        let imageUrl = image.image_url || image.url || '';
        
        // If the URL doesn't start with http/https, check if it needs uploads/gallery/ prefix
        if (imageUrl && !imageUrl.startsWith('http://') && !imageUrl.startsWith('https://') && !imageUrl.startsWith('/')) {
            // Check if it already has uploads/gallery in the path
            if (!imageUrl.includes('uploads/gallery/') && !imageUrl.includes('uploads\\gallery\\')) {
                // Try with uploads/gallery/ prefix
                if (!imageUrl.startsWith('uploads/')) {
                    imageUrl = 'uploads/gallery/' + imageUrl;
                }
            }
        }
        
        img.src = imageUrl;
        img.alt = image.alt_text || image.alt || 'Barangay project image';
        img.className = 'gallery-photo';
        img.loading = 'lazy'; // Lazy load for better performance
        
        img.onerror = function() {
            console.warn('Failed to load image:', imageUrl);
            // Try alternative path
            const altUrl = imageUrl.includes('uploads/gallery/') ? imageUrl.replace('uploads/gallery/', '') : 'uploads/gallery/' + imageUrl;
            if (altUrl !== imageUrl) {
                this.src = altUrl;
            } else {
                // Show broken image indicator but keep structure
                this.style.opacity = '0.3';
                this.style.filter = 'grayscale(100%)';
            }
        };
        
        img.onload = function() {
            console.log('Successfully loaded image:', imageUrl);
        };
        
        galleryTrack.appendChild(img);
    });
    
    // Log for debugging
    console.log('Gallery rendered with', images.length * 2, 'images (duplicated for scrolling)');
}

// Tab switching functionality
function showTab(tabName) {
    // Hide all content sections
    document.querySelectorAll('.content-section').forEach(section => {
        section.classList.add('hidden');
    });
    
    // Remove active class from all nav tabs
    document.querySelectorAll('.nav-tab').forEach(tab => {
        tab.classList.remove('active');
    });
    
    // Show selected content section
    const contentSection = document.getElementById(`content-${tabName}`);
    if (contentSection) {
        contentSection.classList.remove('hidden');
    }
    
    // Add active class to selected nav tab
    const navTab = document.getElementById(`nav-${tabName}`);
    if (navTab) {
        navTab.classList.add('active');
    }
    
    // Render content if needed - always reload to show latest data
    if (tabName === 'home') {
        renderDashboardStats();
        generateContractsFromBudget();
        renderContracts();
    } else if (tabName === 'budget') {
        // Always reload budget data to show latest
        loadBudgetData().then(() => {
            renderBudget();
        }).catch(() => {
            // If load fails, try to render with existing data
            if (budgetData.length > 0) {
                renderBudget();
            }
        });
    } else if (tabName === 'announcement') {
        // Always reload announcements to show latest posts from admin
        loadPosts().then(() => {
            renderPublicPosts();
        }).catch(() => {
            // If load fails, try to render with existing data
            if (posts.length > 0) {
                renderPublicPosts();
            } else {
                const postsContainer = document.getElementById('public-posts');
                if (postsContainer) {
                    postsContainer.innerHTML = '<p class="text-gray-500 text-center py-8">Loading announcements...</p>';
                }
            }
        });
    } else if (tabName === 'barangay') {
        // Always reload gallery images to show latest project images
        // Small delay to ensure the section is visible before loading
        setTimeout(() => {
            loadGalleryImages().then(() => {
                // If there were pending images, render them now
                if (window.pendingGalleryImages) {
                    renderGalleryImages(window.pendingGalleryImages);
                }
            });
        }, 100);
    }
}

// Render dashboard statistics
function renderDashboardStats() {
    const statusCardsContainer = document.getElementById('status-cards-container');
    if (!statusCardsContainer) return;
    
    // Calculate statistics from budget data
    let totalInvestment = 0;
    let totalContracts = 0;
    let forProcurement = 0;
    let notYetStarted = 0;
    let ongoing = 0;
    let completed = 0;
    let terminated = 0;
    
    budgetData.forEach(item => {
        totalInvestment += item.allocated;
        totalContracts++;
        
        if (item.status === 'Pending') {
            forProcurement++;
        } else if (item.status === 'Initial') {
            notYetStarted++;
        } else if (item.status === 'Ongoing') {
            ongoing++;
        } else if (item.status === 'Completed') {
            completed++;
        } else {
            terminated++;
        }
    });
    
    // Update header stats
    const totalInvestmentEl = document.getElementById('dashboard-total-investment');
    const totalContractsEl = document.getElementById('dashboard-total-contracts');
    
    if (totalInvestmentEl) {
        const investmentInTrillions = (totalInvestment / 1000000000000).toFixed(3);
        totalInvestmentEl.textContent = `₱${investmentInTrillions}T`;
    }
    if (totalContractsEl) {
        totalContractsEl.textContent = totalContracts.toLocaleString();
    }
    
    // Render status cards
    statusCardsContainer.innerHTML = '';
    
    const statusCards = [
        {
            icon: '⚖️',
            number: forProcurement,
            percentage: totalContracts > 0 ? ((forProcurement / totalContracts) * 100).toFixed(1) : '0',
            status: 'For Procurement',
            subtitle: 'Bidding Phase',
            color: 'purple'
        },
        {
            icon: '⏰',
            number: notYetStarted,
            percentage: totalContracts > 0 ? ((notYetStarted / totalContracts) * 100).toFixed(1) : '0',
            status: 'Not Yet Started',
            subtitle: 'Awaiting Start',
            color: 'blue'
        },
        {
            icon: '🔄',
            number: ongoing,
            percentage: totalContracts > 0 ? ((ongoing / totalContracts) * 100).toFixed(1) : '0',
            status: 'Ongoing Contracts',
            subtitle: 'In Progress',
            color: 'orange'
        },
        {
            icon: '✅',
            number: completed,
            percentage: totalContracts > 0 ? ((completed / totalContracts) * 100).toFixed(1) : '0',
            status: 'Completed Contracts',
            subtitle: 'Built As Specified',
            color: 'green'
        },
        {
            icon: '❌',
            number: terminated,
            percentage: totalContracts > 0 ? ((terminated / totalContracts) * 100).toFixed(1) : '0',
            status: 'Terminated Contracts',
            subtitle: 'Discontinued',
            color: 'red'
        }
    ];
    
    statusCards.forEach(card => {
        const cardDiv = document.createElement('div');
        cardDiv.className = 'status-card';
        
        const colorClasses = {
            purple: 'text-purple-600',
            blue: 'text-blue-600',
            orange: 'text-orange-600',
            green: 'text-green-600',
            red: 'text-red-600'
        };
        
        cardDiv.innerHTML = `
            <div class="flex items-center gap-4 mb-4">
                <span class="text-4xl">${card.icon}</span>
                <div>
                    <div class="text-3xl font-bold ${colorClasses[card.color]}">${card.number.toLocaleString()}</div>
                    <div class="text-sm text-gray-500">(${card.percentage}%)</div>
                </div>
            </div>
            <div class="font-bold text-gray-700">${card.status}</div>
            <div class="text-sm text-gray-500">${card.subtitle}</div>
        `;
        
        statusCardsContainer.appendChild(cardDiv);
    });
}

// Generate contracts data from budget data
function generateContractsFromBudget() {
    const year = new Date().getFullYear();
    const yearPrefix = String(year).substring(2);
    
    contractsData = budgetData.map((item, index) => {
        const accomplishment = item.allocated > 0 ? ((item.spent / item.allocated) * 100).toFixed(2) : '0';
        const completionDate = item.status === 'Completed' ? 
            new Date(Date.now() - Math.random() * 365 * 24 * 60 * 60 * 1000).toLocaleDateString('en-US', { month: '2-digit', day: '2-digit', year: 'numeric' }) : 
            '—';
        
        // Generate implementing office based on category
        const offices = ['Eastern Samar DEO', 'Capiz 1st DEO', 'Region IX', 'Ilocos Norte 2nd DEO', 'Iloilo 3rd DEO', 'Leyte 1st DEO', 'Catanduanes DEO'];
        const implementingOffice = offices[index % offices.length];
        
        // Generate contractor name
        const contractors = [
            'PABLO S. LABASBAS CONSTRUCTION CORPORATION (FORMERLY: PABLOS. LABASBAS CONSTRUCTION CORPORATION)',
            'SALMEGA BUILDERS',
            'ZAMBO J.A. BUILDERS',
            'CURRIMAO CONSTRUCTION & DEVELOPMENT, INC.',
            'VIRGIN BUILDERS CONSTRUCTION',
            'R8 ASPHALT PLANT AND CONSTRUCTION INC. (FORMERLY:R8 ASPHALT PLANT AND CONSTRUCTION)',
            'VIRGO CONSTRUCTION'
        ];
        const contractorIds = [6302, 45281, 40678, 5129, 36201, 46908, 31068];
        const contractor = contractors[index % contractors.length] + ` (${contractorIds[index % contractorIds.length]})`;
        
        // Generate contract description based on category
        let contractDescription = '';
        let category = 'Roads';
        
        if (item.category.includes('Infrastructure') || item.category.includes('Development')) {
            category = 'Roads';
            const roadTypes = [
                'CONSTRUCTION OF CONCRETE ROAD',
                'CONSTRUCTION OF ROAD',
                'REHABILITATION/ RECONSTRUCTION/ UPGRADING OF DAMAGED PAVED ROADS',
                'ASSET PRESERVATION-PREVENTIVE MAINTENANCE-TERIARY ROADS',
                'CONSTRUCTION OF ROAD SLOPE PROTECTION STRUCTURE',
                'CONVERGENCE AND SPECIAL SUPPORT PROGRAM - CONSTRUCTION / REHABILITATION OF'
            ];
            contractDescription = `${yearPrefix}${String.fromCharCode(65 + (index % 26))}${String.fromCharCode(66 + (index % 26))}${String(Math.floor(Math.random() * 10000)).padStart(4, '0')} - ${roadTypes[index % roadTypes.length]}`;
        } else if (item.category.includes('Personnel') || item.category.includes('MOOE')) {
            category = 'Buildings and Facilities';
            contractDescription = `${yearPrefix}${String.fromCharCode(73 + (index % 26))}${String.fromCharCode(66 + (index % 26))}${String(Math.floor(Math.random() * 10000)).padStart(4, '0')} - CONSTRUCTION OF 1-STOREY, 2-CLASS ROOM SCHOOL BUILDING`;
        } else {
            category = 'Buildings and Facilities';
            contractDescription = `${yearPrefix}${String.fromCharCode(71 + (index % 26))}${String.fromCharCode(72 + (index % 26))}${String(Math.floor(Math.random() * 10000)).padStart(4, '0')} - CONSTRUCTION OF MULTI-PURPOSE BUILDING`;
        }
        
        return {
            id: item.id,
            contractDescription: contractDescription,
            category: category,
            implementingOffice: implementingOffice,
            contractor: contractor,
            cost: item.allocated,
            accomplishment: `${accomplishment}%`,
            accomplishmentPercent: parseFloat(accomplishment),
            completionDate: completionDate,
            status: item.status
        };
    });
    
    filteredContracts = [...contractsData];
}

// Render contracts table
function renderContracts() {
    const tableBody = document.querySelector('#contractsTable tbody');
    if (!tableBody) return;
    
    tableBody.innerHTML = '';
    
    if (filteredContracts.length === 0) {
        const row = document.createElement('tr');
        row.innerHTML = '<td colspan="7" class="px-6 py-8 text-center text-gray-500">No contracts found</td>';
        tableBody.appendChild(row);
        return;
    }
    
    filteredContracts.forEach(contract => {
        const row = document.createElement('tr');
        row.className = 'contract-row';
        
        row.innerHTML = `
            <td class="px-6 py-4">
                <div class="font-semibold text-gray-900">${contract.contractDescription}</div>
                <div class="text-sm text-gray-500 mt-1">${contract.category}</div>
            </td>
            <td class="px-6 py-4 text-sm text-gray-700">${contract.implementingOffice}</td>
            <td class="px-6 py-4 text-sm text-gray-700">${contract.contractor}</td>
            <td class="px-6 py-4 text-sm font-semibold text-gray-700">${formatCurrency(contract.cost)}</td>
            <td class="px-6 py-4 text-sm font-semibold text-gray-700">${contract.accomplishment}</td>
            <td class="px-6 py-4 text-sm text-gray-700">${contract.completionDate}</td>
            <td class="px-6 py-4">
                <button class="px-4 py-2 bg-orange-500 text-white text-sm font-semibold rounded-lg hover:bg-orange-600 transition duration-150">
                    • REPORT
                </button>
            </td>
        `;
        
        tableBody.appendChild(row);
    });
}

// Search functionality for contracts
function setupContractSearch() {
    const searchInput = document.getElementById('contract-search');
    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            const searchTerm = e.target.value.toLowerCase().trim();
            
            if (searchTerm === '') {
                filteredContracts = [...contractsData];
            } else {
                filteredContracts = contractsData.filter(contract => 
                    contract.contractDescription.toLowerCase().includes(searchTerm) ||
                    contract.implementingOffice.toLowerCase().includes(searchTerm) ||
                    contract.contractor.toLowerCase().includes(searchTerm) ||
                    contract.category.toLowerCase().includes(searchTerm)
                );
            }
            
            renderContracts();
        });
    }
}

async function initializeApp() {
    await Promise.all([loadBudgetData(), loadPosts(), loadGalleryImages()]);
    
    // Generate contracts from budget data
    generateContractsFromBudget();
    
    // Initialize with home tab
    showTab('home');
    setupContractSearch();

    const userInput = document.getElementById('user-input');
    if (userInput) {
        userInput.addEventListener('keypress', (event) => {
            if (event.key === 'Enter') {
                handleUserMessage();
            }
        });
    }
    
    // Refresh gallery images periodically to show new uploads
    // Refresh more frequently to catch new uploads quickly
    setInterval(() => {
        loadGalleryImages();
    }, 5000); // Refresh every 5 seconds for faster updates
    
    // Load existing messages when chatbot is opened
    const chatbotContainer = document.getElementById('chatbot-container');
    if (chatbotContainer) {
        let messageRefreshInterval = null;
        const observer = new MutationObserver((mutations) => {
            if (!chatbotContainer.classList.contains('hidden')) {
                const conversationId = getConversationId();
                loadUserMessages(conversationId);
                // Start periodic refresh for new admin messages
                if (messageRefreshInterval) clearInterval(messageRefreshInterval);
                messageRefreshInterval = setInterval(() => {
                    loadUserMessages(conversationId);
                }, 3000);
            } else {
                // Stop refresh when chatbot is closed
                if (messageRefreshInterval) {
                    clearInterval(messageRefreshInterval);
                    messageRefreshInterval = null;
                }
            }
        });
        observer.observe(chatbotContainer, { attributes: true, attributeFilter: ['class'] });
    }
}

document.addEventListener('DOMContentLoaded', initializeApp);

function toggleProgress(progressId) {
    const progressDiv = document.getElementById(progressId);
    if (progressDiv) {
        progressDiv.classList.toggle('hidden');
    }
}

window.toggleChatbot = toggleChatbot;
window.handleUserMessage = handleUserMessage;
window.loadGalleryImages = loadGalleryImages;
window.toggleProgress = toggleProgress;
window.attemptLogin = attemptLogin;
window.loadItemForEdit = loadItemForEdit;
window.saveBudgetUpdate = saveBudgetUpdate;
window.showAdminTab = showAdminTab;
window.loadPosts = loadPosts;
window.selectMessageType = selectMessageType;
window.submitConcern = submitConcern;
window.resetChatbot = resetChatbot;
window.showTab = showTab;

