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
let currentConversationId = null;
let chatRefreshInterval = null;
let concernsRefreshInterval = null;

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
        const response = await fetch('api/get_posts.php', { cache: 'no-store' });
        if (!response.ok) throw new Error('Unable to reach the posts API.');
        const data = await response.json();
        posts = Array.isArray(data) ? data : [];
    } catch (error) {
        console.warn('Posts API unavailable, reverting to fallback announcements.', error);
        posts = [...fallbackPosts];
    }
}

function formatCurrency(amount) {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
        minimumFractionDigits: 2,
    }).format(amount);
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
            document.getElementById('logout-btn').classList.remove('hidden');
            enablePostForm(true);

            await Promise.all([loadBudgetData(), loadPosts()]);
            populateAdminSelect();
            renderConcerns();
            loadChatConversations();
            loadAdminGalleryPreview();
            
            // Show live projects preview section
            const previewSection = document.getElementById('live-projects-preview');
            if (previewSection) {
                previewSection.classList.remove('hidden');
            }
            
            // Start auto-refresh for chat
            if (chatRefreshInterval) clearInterval(chatRefreshInterval);
            chatRefreshInterval = setInterval(() => {
                if (currentConversationId) {
                    loadConversationMessages(currentConversationId);
                } else {
                    loadChatConversations();
                }
            }, 3000);
        }
    } catch (error) {
        loginMessage.textContent = error.message;
        loginMessage.classList.remove('hidden');
    }
}

async function logout() {
    try {
        await fetch('api/logout.php', { 
            method: 'POST', 
            credentials: 'include' 
        });
    } catch (error) {
        console.error('Logout error:', error);
    }
    
    isAdminAuthenticated = false;
    document.getElementById('login-form').classList.remove('hidden');
    document.getElementById('admin-dashboard').classList.add('hidden');
    document.getElementById('logout-btn').classList.add('hidden');
    document.getElementById('live-projects-preview').classList.add('hidden');
    
    if (chatRefreshInterval) {
        clearInterval(chatRefreshInterval);
        chatRefreshInterval = null;
    }
    
    // Clear form fields
    document.getElementById('admin-username').value = '';
    document.getElementById('admin-pass').value = '';
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
        document.getElementById('edit-progress').value = item.project_progress || '';
    } else {
        document.getElementById('edit-allocated').value = '';
        document.getElementById('edit-spent').value = '';
        document.getElementById('edit-status').value = 'Initial';
        document.getElementById('edit-progress').value = '';
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
    const projectProgress = document.getElementById('edit-progress').value.trim();

    if (Number.isNaN(allocated) || Number.isNaN(spent) || allocated < spent) {
        updateMessage.textContent = 'Invalid amounts. Allocated must be greater than or equal to Spent.';
        updateMessage.classList.remove('hidden', 'text-green-600');
        updateMessage.classList.add('text-red-600');
        return;
    }

    const payload = { 
        id: selectedItemId, 
        allocated, 
        spent, 
        status,
        project_progress: projectProgress || null
    };
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
            const { id, allocated: alloc, spent: sp, status: stat, project_progress: progress } = result.updatedItem;
            const localIndex = budgetData.findIndex((entry) => entry.id === Number(id));
            if (localIndex > -1) {
                budgetData[localIndex] = {
                    ...budgetData[localIndex],
                    allocated: Number(alloc),
                    spent: Number(sp),
                    status: stat,
                    project_progress: progress || null,
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
        populateAdminSelect();

        updateMessage.textContent = 'Budget category successfully updated!';
        updateMessage.classList.remove('hidden', 'text-red-600');
        updateMessage.classList.add('text-green-600');

        document.getElementById('budget-item-select').value = '';
        loadItemForEdit();
        selectedItemId = null;
        
        // Hide message after 5 seconds
        setTimeout(() => {
            updateMessage.classList.add('hidden');
        }, 5000);
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

    const title = titleInput.value.trim();
    const body = bodyInput.value.trim();
    const imageFile = imageInput.files[0];

    if (!title || !body) {
        formMessage.textContent = 'Title and message are required.';
        formMessage.classList.remove('hidden', 'text-green-600');
        formMessage.classList.add('text-red-600');
        return;
    }

    // Validate file size if image is provided
    if (imageFile) {
        const maxSize = 5 * 1024 * 1024; // 5MB
        if (imageFile.size > maxSize) {
            formMessage.textContent = 'Image file is too large. Maximum size is 5MB.';
            formMessage.classList.remove('hidden', 'text-green-600');
            formMessage.classList.add('text-red-600');
            return;
        }
    }

    try {
        const formData = new FormData();
        formData.append('title', title);
        formData.append('body', body);
        if (imageFile) {
            formData.append('image', imageFile);
        }

        const response = await fetch('api/create_post.php', {
            method: 'POST',
            credentials: 'include',
            body: formData,
        });

        if (!response.ok) {
            const errorPayload = await response.json();
            throw new Error(errorPayload.message || 'Unable to publish post.');
        }

        const result = await response.json();
        if (result.success) {
            formMessage.textContent = 'Announcement published!';
            formMessage.classList.remove('hidden', 'text-red-600');
            formMessage.classList.add('text-green-600');
            document.getElementById('admin-post-form').reset();
            
            // Reload posts if on public view
            if (typeof loadPosts === 'function') {
                loadPosts();
            }
            
            // Hide message after 5 seconds
            setTimeout(() => {
                formMessage.classList.add('hidden');
            }, 5000);
        } else {
            throw new Error(result.message || 'Unable to publish post.');
        }
    } catch (error) {
        formMessage.textContent = error.message;
        formMessage.classList.remove('hidden', 'text-green-600');
        formMessage.classList.add('text-red-600');
    }
}

function showAdminTab(tabName) {
    ['update', 'concerns', 'chat', 'gallery'].forEach((tab) => {
        const tabElement = document.getElementById(`admin-tab-${tab}`);
        const buttonElement = document.getElementById(`tab-${tab}`);
        if (tabElement) tabElement.classList.add('hidden');
        if (buttonElement) buttonElement.classList.remove('active');
    });

    const tabElement = document.getElementById(`admin-tab-${tabName}`);
    const buttonElement = document.getElementById(`tab-${tabName}`);
    if (tabElement) tabElement.classList.remove('hidden');
    if (buttonElement) buttonElement.classList.add('active');
    
    if (tabName === 'chat') {
        loadChatConversations();
    } else if (tabName === 'gallery') {
        loadGalleryImages();
        loadAdminGalleryPreview();
    }
}

async function renderConcerns(status = null) {
    const listContainer = document.getElementById('concerns-list');
    const noConcernsMsg = document.getElementById('no-concerns');
    if (!listContainer) return;
    
    try {
        const url = status ? `api/get_concerns.php?status=${status}` : 'api/get_concerns.php';
        const response = await fetch(url, {
            credentials: 'include',
            cache: 'no-store'
        });
        
        if (!response.ok) throw new Error('Unable to load concerns.');
        
        const result = await response.json();
        if (!result.success) throw new Error(result.message || 'Failed to load concerns.');
        
        const concerns = result.concerns || [];
        
        if (concerns.length === 0) {
            listContainer.innerHTML = '<p class="text-gray-500" id="no-concerns">No concerns found.</p>';
            return;
        }
        
        listContainer.innerHTML = '';
        
        concerns.forEach((concern) => {
            const concernDiv = document.createElement('div');
            concernDiv.className = 'p-4 bg-white border border-gray-200 rounded-lg shadow-sm';
            
            const statusColor = {
                'Pending': 'bg-yellow-100 text-yellow-800',
                'In Progress': 'bg-blue-100 text-blue-800',
                'Resolved': 'bg-green-100 text-green-800'
            }[concern.status] || 'bg-gray-100 text-gray-800';
            
            concernDiv.innerHTML = `
                <div class="flex justify-between items-start mb-3">
                    <div>
                        <span class="inline-block px-2 py-1 text-xs font-semibold rounded-full ${statusColor}">
                            ${concern.status}
                        </span>
                        <span class="ml-2 text-sm font-bold text-brgy-primary">
                            ${concern.concern_type}
                        </span>
                    </div>
                    <span class="text-xs text-gray-500">
                        ${new Date(concern.created_at).toLocaleDateString()} ${new Date(concern.created_at).toLocaleTimeString()}
                    </span>
                </div>
                <div class="mb-2">
                    <p class="font-semibold text-gray-700">${concern.name}</p>
                    ${concern.email ? `<p class="text-sm text-gray-600">${concern.email}</p>` : ''}
                </div>
                <div class="mb-3">
                    <p class="text-gray-700">${concern.message}</p>
                </div>
                ${concern.admin_response ? `
                    <div class="bg-gray-50 p-3 rounded-lg mb-3">
                        <p class="text-sm font-semibold text-gray-700 mb-1">Admin Response:</p>
                        <p class="text-gray-600">${concern.admin_response}</p>
                    </div>
                ` : ''}
                <div class="flex gap-2">
                    <select id="status-${concern.id}" class="px-2 py-1 border border-gray-300 rounded text-sm">
                        <option value="Pending" ${concern.status === 'Pending' ? 'selected' : ''}>Pending</option>
                        <option value="In Progress" ${concern.status === 'In Progress' ? 'selected' : ''}>In Progress</option>
                        <option value="Resolved" ${concern.status === 'Resolved' ? 'selected' : ''}>Resolved</option>
                    </select>
                    <button onclick="updateConcernStatus(${concern.id})" class="px-3 py-1 bg-brgy-primary text-white text-sm rounded hover:bg-emerald-700">
                        Update
                    </button>
                </div>
                <div class="mt-2">
                    <textarea id="response-${concern.id}" placeholder="Add admin response..." class="w-full p-2 border border-gray-300 rounded text-sm" rows="2">${concern.admin_response || ''}</textarea>
                </div>
            `;
            
            listContainer.appendChild(concernDiv);
        });
        
    } catch (error) {
        console.error('Error loading concerns:', error);
        listContainer.innerHTML = '<p class="text-red-500">Error loading concerns. Please try again.</p>';
    }
}

async function updateConcernStatus(concernId) {
    const statusSelect = document.getElementById(`status-${concernId}`);
    const responseTextarea = document.getElementById(`response-${concernId}`);
    
    if (!statusSelect || !responseTextarea) return;
    
    const newStatus = statusSelect.value;
    const adminResponse = responseTextarea.value.trim();
    
    try {
        const response = await fetch('api/update_concern.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            credentials: 'include',
            body: JSON.stringify({
                concern_id: concernId,
                status: newStatus,
                admin_response: adminResponse
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Refresh concerns list
            const statusFilter = document.getElementById('concern-status-filter').value;
            renderConcerns(statusFilter || null);
        } else {
            alert('Error updating concern: ' + result.message);
        }
    } catch (error) {
        console.error('Error updating concern:', error);
        alert('Failed to update concern. Please try again.');
    }
}

function filterConcerns() {
    const statusFilter = document.getElementById('concern-status-filter').value;
    renderConcerns(statusFilter || null);
}

function refreshConcerns() {
    const statusFilter = document.getElementById('concern-status-filter').value;
    renderConcerns(statusFilter || null);
}

async function loadChatConversations() {
    try {
        const response = await fetch('api/get_messages.php?admin=1', {
            credentials: 'include',
            cache: 'no-store'
        });
        
        if (!response.ok) throw new Error('Unable to load conversations.');
        
        const result = await response.json();
        if (!result.success) throw new Error(result.message || 'Failed to load conversations.');
        
        renderChatList(result.conversations || []);
        updateChatCount(result.conversations?.length || 0);
    } catch (error) {
        console.error('Failed to load conversations:', error);
        const listContainer = document.getElementById('chat-list');
        if (listContainer) {
            listContainer.innerHTML = '<p class="text-red-500">Failed to load conversations. Please refresh.</p>';
        }
    }
}

function renderChatList(conversations) {
    const listContainer = document.getElementById('chat-list');
    if (!listContainer) return;
    
    listContainer.innerHTML = '';
    
    if (conversations.length === 0) {
        listContainer.innerHTML = '<p class="text-gray-500" id="no-chats">No messages yet.</p>';
        return;
    }
    
    conversations.forEach((conv) => {
        const convDiv = document.createElement('div');
        convDiv.className = 'p-4 bg-white border border-gray-200 rounded-lg shadow-sm cursor-pointer hover:bg-gray-50 transition';
        convDiv.onclick = () => openConversation(conv.conversation_id);
        convDiv.innerHTML = `
            <div class="flex justify-between items-start">
                <div>
                    <p class="font-semibold text-brgy-primary">Conversation: ${conv.conversation_id.substring(0, 20)}...</p>
                    <p class="text-xs text-gray-500 mt-1">${conv.message_count} message(s)</p>
                </div>
                <p class="text-xs text-gray-400">${new Date(conv.last_message).toLocaleString()}</p>
            </div>
        `;
        listContainer.appendChild(convDiv);
    });
}

function updateChatCount(count) {
    const counter = document.getElementById('chat-count');
    if (counter) counter.textContent = count;
}

async function openConversation(conversationId) {
    currentConversationId = conversationId;
    document.getElementById('chat-list').classList.add('hidden');
    document.getElementById('chat-conversation').classList.remove('hidden');
    document.getElementById('conversation-title').textContent = `Conversation: ${conversationId.substring(0, 20)}...`;
    document.getElementById('current-conversation-id').value = conversationId;
    
    await loadConversationMessages(conversationId);
}

function closeConversation() {
    currentConversationId = null;
    document.getElementById('chat-list').classList.remove('hidden');
    document.getElementById('chat-conversation').classList.add('hidden');
    document.getElementById('conversation-messages').innerHTML = '';
    document.getElementById('current-conversation-id').value = '';
}

async function loadConversationMessages(conversationId) {
    try {
        const response = await fetch(`api/get_messages.php?conversation_id=${encodeURIComponent(conversationId)}&sender_type=admin`, {
            credentials: 'include',
            cache: 'no-store'
        });
        
        if (!response.ok) throw new Error('Unable to load messages.');
        
        const result = await response.json();
        if (!result.success) throw new Error(result.message || 'Failed to load messages.');
        
        renderConversationMessages(result.messages || []);
    } catch (error) {
        console.error('Failed to load messages:', error);
    }
}

function renderConversationMessages(messages) {
    const container = document.getElementById('conversation-messages');
    if (!container) return;
    
    container.innerHTML = '';
    
    messages.forEach((msg) => {
        const msgDiv = document.createElement('div');
        msgDiv.className = `message-box ${msg.sender_type === 'admin' ? 'user' : 'bot'}`;
        msgDiv.innerHTML = `
            <div class="font-semibold text-xs mb-1">${msg.sender_type === 'admin' ? 'Admin' : 'User'}</div>
            <div>${msg.message}</div>
            <div class="text-xs mt-1 opacity-70">${new Date(msg.created_at).toLocaleString()}</div>
        `;
        container.appendChild(msgDiv);
    });
    
    container.scrollTop = container.scrollHeight;
}

async function sendAdminReply() {
    const input = document.getElementById('admin-reply-input');
    const conversationId = document.getElementById('current-conversation-id').value;
    const message = input.value.trim();
    
    if (!message || !conversationId) return;
    
    try {
        const response = await fetch('api/send_message.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify({
                conversation_id: conversationId,
                sender_type: 'admin',
                message: message
            })
        });
        
        if (!response.ok) throw new Error('Failed to send message.');
        
        const result = await response.json();
        if (result.success) {
            input.value = '';
            await loadConversationMessages(conversationId);
        }
    } catch (error) {
        console.error('Failed to send message:', error);
        alert('Failed to send message. Please try again.');
    }
}

let galleryImages = [];

async function loadGalleryImages() {
    try {
        const response = await fetch('api/get_gallery.php', { cache: 'no-store' });
        if (!response.ok) throw new Error('Unable to load gallery images.');
        
        galleryImages = await response.json();
        renderGalleryImages();
    } catch (error) {
        console.error('Failed to load gallery:', error);
        const listContainer = document.getElementById('gallery-images-list');
        if (listContainer) {
            listContainer.innerHTML = '<p class="text-red-500">Failed to load gallery images. Please refresh.</p>';
        }
    }
}

function renderGalleryImages() {
    const listContainer = document.getElementById('gallery-images-list');
    if (!listContainer) return;
    
    listContainer.innerHTML = '';
    
    if (galleryImages.length === 0) {
        listContainer.innerHTML = '<p class="text-gray-500">No images in gallery yet. Add your first image above!</p>';
        return;
    }
    
    galleryImages.forEach((image) => {
        const imageDiv = document.createElement('div');
        imageDiv.className = 'bg-gray-50 border border-gray-200 rounded-lg p-4';
        imageDiv.innerHTML = `
            <div class="mb-3">
                <img src="${image.image_url}" alt="${image.alt_text}" class="w-full h-40 object-cover rounded-lg" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'320\' height=\'200\'%3E%3Crect fill=\'%23ddd\' width=\'320\' height=\'200\'/%3E%3Ctext fill=\'%23999\' x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dy=\'.3em\'%3EImage not found%3C/text%3E%3C/svg%3E'">
            </div>
            <p class="text-sm font-medium text-gray-700 mb-1">${image.alt_text}</p>
            <p class="text-xs text-gray-500 mb-2">Order: ${image.display_order}</p>
            <button onclick="deleteGalleryImage(${image.id})" class="w-full py-2 bg-red-500 text-white font-semibold rounded-lg hover:bg-red-600 transition duration-150 text-sm">Delete</button>
        `;
        listContainer.appendChild(imageDiv);
    });
}

async function addGalleryImage(event) {
    event.preventDefault();
    
    const fileInput = document.getElementById('gallery-image-file');
    const altInput = document.getElementById('gallery-alt-text');
    const orderInput = document.getElementById('gallery-display-order');
    const messageEl = document.getElementById('gallery-add-message');
    
    messageEl.classList.add('hidden');
    
    const file = fileInput.files[0];
    const altText = altInput.value.trim() || 'Barangay project image';
    const displayOrder = parseInt(orderInput.value) || 0;
    
    if (!file) {
        messageEl.textContent = 'Please select an image file.';
        messageEl.classList.remove('hidden', 'text-green-600');
        messageEl.classList.add('text-red-600');
        return;
    }
    
    // Validate file type
    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    if (!allowedTypes.includes(file.type)) {
        messageEl.textContent = 'Invalid file type. Please upload a JPG, PNG, GIF, or WebP image.';
        messageEl.classList.remove('hidden', 'text-green-600');
        messageEl.classList.add('text-red-600');
        return;
    }
    
    // Validate file size (5MB max)
    const maxSize = 5 * 1024 * 1024; // 5MB
    if (file.size > maxSize) {
        messageEl.textContent = 'File is too large. Maximum size is 5MB.';
        messageEl.classList.remove('hidden', 'text-green-600');
        messageEl.classList.add('text-red-600');
        return;
    }
    
    // Show uploading message
    messageEl.textContent = 'Uploading image...';
    messageEl.classList.remove('hidden', 'text-red-600', 'text-green-600');
    messageEl.classList.add('text-blue-600');
    
    try {
        // Create FormData for file upload
        const formData = new FormData();
        formData.append('image', file);
        formData.append('alt_text', altText);
        formData.append('display_order', displayOrder);
        
        const response = await fetch('api/add_gallery_image.php', {
            method: 'POST',
            credentials: 'include',
            body: formData
        });
        
        // Get response text first to check if it's JSON
        const responseText = await response.text();
        let result;
        
        try {
            result = JSON.parse(responseText);
        } catch (parseError) {
            // If response is not JSON, it's likely an HTML error page
            console.error('Non-JSON response:', responseText);
            throw new Error('Server returned an error. Please check if the database is set up correctly.');
        }
        
        if (!response.ok) {
            throw new Error(result.message || 'Failed to upload image.');
        }
        
        if (result.success) {
            messageEl.textContent = 'Image uploaded successfully!';
            messageEl.classList.remove('hidden', 'text-red-600', 'text-blue-600');
            messageEl.classList.add('text-green-600');
            
            document.getElementById('gallery-add-form').reset();
            document.getElementById('gallery-display-order').value = '0';
            
            await loadGalleryImages();
            await loadAdminGalleryPreview(); // Refresh preview after upload
        } else {
            throw new Error(result.message || 'Failed to upload image.');
        }
    } catch (error) {
        messageEl.textContent = error.message || 'An error occurred while uploading the image.';
        messageEl.classList.remove('hidden', 'text-green-600', 'text-blue-600');
        messageEl.classList.add('text-red-600');
    }
}

async function deleteGalleryImage(imageId) {
    if (!confirm('Are you sure you want to delete this image?')) return;
    
    try {
        const response = await fetch('api/delete_gallery_image.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify({ id: imageId })
        });
        
        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.message || 'Failed to delete image.');
        }
        
        const result = await response.json();
        if (result.success) {
            await loadGalleryImages();
            await loadAdminGalleryPreview(); // Refresh preview after delete
        }
    } catch (error) {
        alert('Failed to delete image: ' + error.message);
    }
}

async function loadAdminGalleryPreview() {
    try {
        const response = await fetch('api/get_gallery.php', { cache: 'no-store' });
        if (!response.ok) throw new Error('Unable to load gallery images.');
        
        const images = await response.json();
        renderAdminGalleryPreview(images);
    } catch (error) {
        console.error('Failed to load gallery preview:', error);
        renderAdminGalleryPreview([]);
    }
}

function renderAdminGalleryPreview(images) {
    const galleryTrack = document.getElementById('admin-gallery-preview');
    if (!galleryTrack) return;
    
    galleryTrack.innerHTML = '';
    
    if (images.length === 0) {
        galleryTrack.innerHTML = '<p class="text-gray-400 text-center py-8">No images in gallery yet. Upload images above to see them here.</p>';
        return;
    }
    
    // Add images twice for seamless scrolling (same as user view)
    [...images, ...images].forEach((image) => {
        const img = document.createElement('img');
        img.src = image.image_url;
        img.alt = image.alt_text || 'Barangay project image';
        img.className = 'gallery-photo';
        img.onerror = function() {
            this.style.opacity = '0.3';
        };
        galleryTrack.appendChild(img);
    });
}

async function initializeApp() {
    // Check if already logged in (from PHP session)
    const dashboard = document.getElementById('admin-dashboard');
    if (dashboard && !dashboard.classList.contains('hidden')) {
        // Already logged in, initialize dashboard
        isAdminAuthenticated = true;
        enablePostForm(true);
        
        await Promise.all([loadBudgetData(), loadPosts()]);
        populateAdminSelect();
        renderConcerns();
        loadChatConversations();
        loadAdminGalleryPreview();
        
        // Start auto-refresh for concerns and chat
        if (concernsRefreshInterval) clearInterval(concernsRefreshInterval);
        concernsRefreshInterval = setInterval(() => {
            renderConcerns();
        }, 5000);
        
        if (chatRefreshInterval) clearInterval(chatRefreshInterval);
        chatRefreshInterval = setInterval(() => {
            if (currentConversationId) {
                loadConversationMessages(currentConversationId);
            } else {
                loadChatConversations();
            }
        }, 3000);
    } else {
        enablePostForm(false);
    }

    const adminPostForm = document.getElementById('admin-post-form');
    if (adminPostForm) {
        adminPostForm.addEventListener('submit', submitAdminPost);
    }
    
    const logoutBtn = document.getElementById('logout-btn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', logout);
    }
    
    const replyInput = document.getElementById('admin-reply-input');
    if (replyInput) {
        replyInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                sendAdminReply();
            }
        });
    }
    
    const galleryAddForm = document.getElementById('gallery-add-form');
    if (galleryAddForm) {
        galleryAddForm.addEventListener('submit', addGalleryImage);
    }
    
    const addProjectForm = document.getElementById('add-project-form');
    if (addProjectForm) {
        addProjectForm.addEventListener('submit', addNewProject);
    }
}

async function addNewProject(event) {
    event.preventDefault();
    const messageEl = document.getElementById('add-project-message');
    messageEl.classList.add('hidden');
    
    const category = document.getElementById('new-category').value.trim();
    const allocated = parseFloat(document.getElementById('new-allocated').value);
    const spent = parseFloat(document.getElementById('new-spent').value);
    const status = document.getElementById('new-status').value;
    const progress = document.getElementById('new-progress').value.trim();
    
    if (!category) {
        messageEl.textContent = 'Please enter a project/category name.';
        messageEl.classList.remove('hidden', 'text-green-600');
        messageEl.classList.add('text-red-600');
        return;
    }
    
    if (Number.isNaN(allocated) || Number.isNaN(spent) || allocated < 0 || spent < 0) {
        messageEl.textContent = 'Please enter valid amounts.';
        messageEl.classList.remove('hidden', 'text-green-600');
        messageEl.classList.add('text-red-600');
        return;
    }
    
    if (allocated < spent) {
        messageEl.textContent = 'Allocated amount must be greater than or equal to spent amount.';
        messageEl.classList.remove('hidden', 'text-green-600');
        messageEl.classList.add('text-red-600');
        return;
    }
    
    try {
        const response = await fetch('api/add_budget_item.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify({
                category,
                allocated,
                spent,
                status,
                project_progress: progress || null
            }),
        });
        
        if (!response.ok) {
            const errorData = await response.json().catch(() => ({ message: 'Failed to add project.' }));
            throw new Error(errorData.message || 'Failed to add project.');
        }
        
        const result = await response.json();
        if (!result.success) {
            throw new Error(result.message || 'Failed to add project.');
        }
        
        // Clear form
        document.getElementById('add-project-form').reset();
        
        // Show success message
        messageEl.textContent = result.message || 'Project added successfully!';
        messageEl.classList.remove('hidden', 'text-red-600');
        messageEl.classList.add('text-green-600');
        
        // Reload budget data and refresh dropdown
        await loadBudgetData();
        populateAdminSelect();
        
        // Hide message after 5 seconds
        setTimeout(() => {
            messageEl.classList.add('hidden');
        }, 5000);
        
    } catch (error) {
        console.error('Add project error:', error);
        messageEl.textContent = `Error: ${error.message}`;
        messageEl.classList.remove('hidden', 'text-green-600');
        messageEl.classList.add('text-red-600');
    }
}

document.addEventListener('DOMContentLoaded', initializeApp);

window.attemptLogin = attemptLogin;
window.loadItemForEdit = loadItemForEdit;
window.addNewProject = addNewProject;
window.saveBudgetUpdate = saveBudgetUpdate;
window.showAdminTab = showAdminTab;
window.closeConversation = closeConversation;
window.sendAdminReply = sendAdminReply;
window.deleteGalleryImage = deleteGalleryImage;
window.updateConcernStatus = updateConcernStatus;
window.filterConcerns = filterConcerns;
window.refreshConcerns = refreshConcerns;

