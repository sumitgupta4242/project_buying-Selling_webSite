document.addEventListener('DOMContentLoaded', () => {
    const projectsContainer = document.getElementById('projects-container');
    const loadingSpinner = document.getElementById('loading-spinner');
    const logoutBtn = document.getElementById('logoutBtn');

    logoutBtn.addEventListener('click', async () => {
        await fetch('../php/auth/logout.php');
        window.location.href = '../index.html';
    });

    const getStatusBadge = (status) => {
        switch (status) {
            case 'pending':
                return `<span class="px-2 py-1 text-xs font-semibold text-yellow-800 bg-yellow-200 rounded-full">Pending Review</span>`;
            case 'approved':
                return `<span class="px-2 py-1 text-xs font-semibold text-green-800 bg-green-200 rounded-full">Approved</span>`;
            case 'requires_changes':
                return `<span class="px-2 py-1 text-xs font-semibold text-red-800 bg-red-200 rounded-full">Requires Changes</span>`;
            default:
                return `<span class="px-2 py-1 text-xs font-semibold text-gray-800 bg-gray-200 rounded-full">${status}</span>`;
        }
    };

    const fetchProjects = async () => {
        try {
            const response = await fetch('../php/project/get_my_projects.php');
            if (response.status === 401) {
                window.location.href = '../index.html'; // Redirect if not logged in
                return;
            }
            const data = await response.json();
            loadingSpinner.style.display = 'none';

            if (data.success && data.projects.length > 0) {
                data.projects.forEach(project => {
                    const projectCard = document.createElement('div');
                    projectCard.className = 'border-b last:border-b-0 py-4';
                    
                    let reviewHtml = '';
                    if (project.admin_review) {
                        reviewHtml = `
                            <div class="mt-3 p-3 bg-gray-50 rounded-lg border border-gray-200">
                                <p class="font-semibold text-sm text-gray-700">Admin Feedback:</p>
                                <p class="text-sm text-gray-600 whitespace-pre-wrap">${project.admin_review}</p>
                            </div>
                        `;
                    }

                    let actionButton = '';
                    if (project.status === 'pending' || project.status === 'requires_changes') {
                        actionButton = `<a href="../edit_project.html?id=${project.id}" class="text-indigo-600 hover:text-indigo-800 font-medium">Edit</a>`;
                    }

                    projectCard.innerHTML = `
                        <div class="flex justify-between items-start">
                            <div>
                                <h3 class="text-lg font-bold text-gray-800">${project.title}</h3>
                                <p class="text-sm text-gray-500">Subject: ${project.subject}</p>
                                <p class="text-sm text-gray-400">Submitted: ${new Date(project.submitted_at).toLocaleDateString()}</p>
                            </div>
                            <div class="text-right">
                                ${getStatusBadge(project.status)}
                                <div class="mt-2">
                                    ${actionButton}
                                </div>
                            </div>
                        </div>
                        ${reviewHtml}
                    `;
                    projectsContainer.appendChild(projectCard);
                });
            } else {
                projectsContainer.innerHTML = '<p>You have not submitted any projects yet.</p>';
            }
        } catch (error) {
            console.error('Error fetching projects:', error);
            projectsContainer.innerHTML = '<p class="text-red-500">Failed to load projects.</p>';
        }
    };

    fetchProjects();
});