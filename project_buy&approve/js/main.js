document.addEventListener('DOMContentLoaded', () => {
    // --- Main Element Selectors ---
    const authSection = document.getElementById('auth-section');
    const dashboardSection = document.getElementById('dashboard-section');
    const flipCard = document.querySelector('.flip-card');
    const aiReviewModal = document.getElementById('ai-review-modal');

    // --- Auth Form Selectors ---
    const loginForm = document.getElementById('loginForm');
    const signupForm = document.getElementById('signupForm');
    const showSignupBtn = document.getElementById('showSignup');
    const showLoginBtn = document.getElementById('showLogin');

    // --- UI State Management Functions ---
    const showDashboard = (name, role) => {
        authSection.style.display = 'none';
        dashboardSection.classList.remove('hidden');
        setTimeout(() => dashboardSection.classList.remove('opacity-0'), 50);

        document.getElementById('welcome-message').textContent = `Welcome, ${name}!`;

        const headerLinks = document.getElementById('header-links');
        headerLinks.innerHTML = ''; // Clear old links

        const dashboardLink = document.createElement('a');
        dashboardLink.href = 'dashboard/';
        dashboardLink.className = 'bg-blue-500 text-white py-2 px-4 rounded-md hover:bg-blue-600 text-sm';
        dashboardLink.textContent = 'My Dashboard';
        headerLinks.appendChild(dashboardLink);

        if (role === 'admin') {
            const adminLink = document.createElement('a');
            adminLink.href = 'admin/';
            adminLink.className = 'bg-purple-500 text-white py-2 px-4 rounded-md hover:bg-purple-600 text-sm ml-2';
            adminLink.textContent = 'Admin Panel';
            headerLinks.appendChild(adminLink);
        }

        const logoutBtn = document.createElement('button');
        logoutBtn.id = 'logoutBtn';
        logoutBtn.className = 'bg-red-500 text-white py-2 px-4 rounded-md hover:bg-red-600 text-sm';
        logoutBtn.textContent = 'Logout';
        headerLinks.appendChild(logoutBtn);
        logoutBtn.addEventListener('click', handleLogout);
    };

    const showAuth = () => {
        dashboardSection.classList.add('opacity-0');
        setTimeout(() => dashboardSection.classList.add('hidden'), 500);
        authSection.style.display = 'block';
        flipCard.classList.remove('flipped');
    };

    // --- Session and Auth Handling ---
    const checkUserSession = async () => {
        try {
            const response = await fetch('php/auth/session_check.php');
            const data = await response.json();
            if (data.loggedIn) {
                showDashboard(data.name, data.role);
            } else {
                showAuth();
            }
        } catch (error) {
            console.error('Session check failed:', error);
            showAuth();
        }
    };

    const handleLogout = async () => {
        await fetch('php/auth/logout.php');
        window.location.reload();
    };

    // Initial check when the page loads
    checkUserSession();

    // --- Auth Form Event Listeners ---
    showSignupBtn.addEventListener('click', (e) => { e.preventDefault(); flipCard.classList.add('flipped'); });
    showLoginBtn.addEventListener('click', (e) => { e.preventDefault(); flipCard.classList.remove('flipped'); });

    signupForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(signupForm);
        const data = Object.fromEntries(formData.entries());
        const response = await fetch('php/auth/register.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ name: data['signup-name'], email: data['signup-email'], password: data['signup-password'] })
        });
        const result = await response.json();
        if (result.success) {
            showDashboard(result.name, result.role);
        } else {
            alert(result.message);
        }
    });

    loginForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(loginForm);
        const data = Object.fromEntries(formData.entries());
        const response = await fetch('php/auth/login.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        const result = await response.json();
        if (result.success) {
            showDashboard(result.name, result.role);
        } else {
            alert(result.message);
        }
    });

    // --- File Input and Submission Logic (delegated to dashboard) ---
    document.body.addEventListener('click', (e) => {
        if (e.target.id === 'logoutBtn') handleLogout();
        if (e.target.id === 'precheck-btn') handlePrecheck();
        if (e.target.id === 'confirm-submit-btn') handleFinalSubmit();
        if (e.target.id === 'close-modal-btn' || e.target.id === 'edit-btn') aiReviewModal.classList.add('hidden');
    });

    function setupFileInput(inputId, labelId, defaultText) {
        const input = document.getElementById(inputId);
        const label = document.getElementById(labelId);
        if (input && label) {
            input.addEventListener('change', () => {
                label.textContent = input.files.length > 0 ? input.files[0].name : defaultText;
            });
        }
    }

    document.body.addEventListener('change', (e) => {
        if (e.target.id === 'zip-file') setupFileInput('zip-file', 'zip-file-name', 'Choose ZIP file...');
        if (e.target.id === 'doc-file') setupFileInput('doc-file', 'doc-file-name', 'Choose document...');
        if (e.target.id === 'run-file') setupFileInput('run-file', 'run-file-name', 'Upload instructions file (optional)...');
    });

    // --- AI Precheck and Final Submit Functions ---
    async function handlePrecheck() {
        const form = document.getElementById('projectForm');
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }
        const btn = document.getElementById('precheck-btn');
        btn.disabled = true;
        btn.textContent = 'Analyzing...';

        const projectData = {
            title: form.querySelector('#project-title').value,
            description: form.querySelector('#project-description').value,
            // Add other fields as needed for AI check
        };
        const promptForAI = `Analyze this project submission. Respond in JSON format with "overallStatus" and "feedbackItems". Rules: Title must be descriptive. Description must be >20 words. \n\n ${JSON.stringify(projectData)}`;

        const aiResult = await callAIPrecheckAPI(promptForAI);
        displayAIReview(aiResult);

        btn.disabled = false;
        btn.textContent = 'Analyze Project with AI';
    }

    async function callAIPrecheckAPI(prompt) {
        try {
            const response = await fetch('php/project/precheck.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ prompt })
            });
            if (!response.ok) throw new Error('Server-side API call failed.');
            const responseData = await response.json();
            const aiText = responseData.candidates[0].content.parts[0].text;
            if (aiText && /\{.*\}/s.test(aiText)) {
                return JSON.parse(aiText.match(/\{.*\}/s)[0]);
            }
            return null;
        } catch (error) {
            return { overallStatus: 'fail', feedbackItems: [{ field: 'API Call', status: 'fail', comment: `API call failed: ${error.message}` }] };
        }
    }
    function displayAIReview(aiResult) {
        const modalContent = document.getElementById('modal-content');
        const confirmBtn = document.getElementById('confirm-submit-btn');
        modalContent.innerHTML = '';
        if (!aiResult || !aiResult.feedbackItems) {
            modalContent.innerHTML = `<p class="text-red-600 font-medium">Could not get a valid review from the AI.</p>`;
            confirmBtn.disabled = true;
            return;
        }

        const list = document.createElement('ul');
        list.className = 'space-y-3';
        aiResult.feedbackItems.forEach(item => {
            const statusIcon = item.status === 'pass'
                ? `<span class="text-green-500 font-bold">✔ Pass:</span>`
                : `<span class="text-red-500 font-bold">❌ Needs Improvement:</span>`;
            list.innerHTML += `<li class="font-semibold text-gray-700">${item.field}</li><li class="pl-4 text-gray-600">${statusIcon} ${item.comment}</li>`;
        });
        modalContent.appendChild(list);

        const criticalFields = ['Project Title', 'Price Range', 'Project Description'];
        const hasCriticalFailure = aiResult.feedbackItems.some(item => criticalFields.includes(item.field) && item.status === 'fail');

        confirmBtn.disabled = hasCriticalFailure;
        confirmBtn.classList.toggle('opacity-50', hasCriticalFailure);
        confirmBtn.classList.toggle('cursor-not-allowed', hasCriticalFailure);

        aiReviewModal.classList.remove('hidden');
    }

    async function handleFinalSubmit(form) {
        const confirmBtn = document.getElementById('confirm-submit-btn');
        if (confirmBtn.disabled) return;
        confirmBtn.disabled = true;
        confirmBtn.textContent = 'Submitting...';

        const formData = new FormData(form);

        try {
            const response = await fetch('php/project/upload.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            if (result.success) {
                alert('Project submitted successfully!');
                aiReviewModal.classList.add('hidden');
                form.reset();
                setupFileInput('zip-file', 'zip-file-name', 'Choose ZIP file...');
                setupFileInput('doc-file', 'doc-file-name', 'Choose document...');
                setupFileInput('run-file', 'run-file-name', 'Upload instructions file (optional)...');
            } else {
                alert(`Submission failed: ${result.message}`);
            }
        } catch (error) {
            console.error('Submission error:', error);
            alert('A network error occurred during submission.');
        } finally {
            confirmBtn.disabled = false;
            confirmBtn.textContent = 'Confirm & Submit Project';
        }
    }
});