document.addEventListener('DOMContentLoaded', () => {
    const projectsContainer = document.getElementById('projects-container');
    const loadingSpinner = document.getElementById('loading-spinner');
    const logoutBtn = document.getElementById('logoutBtn');

    logoutBtn.addEventListener('click', async () => {
        await fetch('../php/auth/logout.php');
        window.location.href = '../index.html';
    });
    
    const fetchAdminProjects = async () => {
        try {
            const response = await fetch('../php/admin/get_all_projects.php');
            if (response.status === 403) { // Forbidden
                loadingSpinner.style.display = 'none';
                projectsContainer.innerHTML = '<p class="text-red-500 font-bold">Access Denied. You must be an administrator to view this page.</p>';
                return;
            }
            const data = await response.json();
            loadingSpinner.style.display = 'none';

            if (data.success && data.projects.length > 0) {
                data.projects.forEach(project => {
                    const projectCard = document.createElement('div');
                    projectCard.className = 'border p-4 rounded-lg';
                    projectCard.dataset.projectId = project.id;
                    
                    projectCard.innerHTML = `
                        <div class="flex justify-between items-start">
                            <div>
                                <h3 class="text-lg font-bold text-gray-800">${project.title}</h3>
                                <p class="text-sm text-gray-500">By: <strong>${project.seller_name}</strong></p>
                                <p class="text-sm text-gray-400">Submitted: ${new Date(project.submitted_at).toLocaleDateString()}</p>
                            </div>
                            <span class="status-text text-sm font-semibold">${project.status}</span>
                        </div>
                        <div class="mt-4">
                            <label for="review-${project.id}" class="block text-sm font-medium text-gray-700">Review / Feedback</label>
                            <textarea id="review-${project.id}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" rows="3">${project.admin_review || ''}</textarea>
                        </div>
                        <div class="mt-4 flex items-center justify-end space-x-4">
                            <label for="status-${project.id}" class="text-sm font-medium text-gray-700">Set Status:</label>
                            <select id="status-${project.id}" class="rounded-md border-gray-300 shadow-sm">
                                <option value="pending" ${project.status === 'pending' ? 'selected' : ''}>Pending</option>
                                <option value="approved" ${project.status === 'approved' ? 'selected' : ''}>Approve</option>
                                <option value="requires_changes" ${project.status === 'requires_changes' ? 'selected' : ''}>Requires Changes</option>
                            </select>
                            <button class="save-btn bg-indigo-600 text-white py-2 px-4 rounded-md hover:bg-indigo-700">Save</button>
                        </div>
                    `;
                    projectsContainer.appendChild(projectCard);
                });
            } else {
                 projectsContainer.innerHTML = '<p>No projects have been submitted yet.</p>';
            }
        } catch (error) {
            console.error('Error fetching projects:', error);
            loadingSpinner.style.display = 'none';
            projectsContainer.innerHTML = '<p class="text-red-500">Failed to load projects.</p>';
        }
    };

    projectsContainer.addEventListener('click', async (e) => {
        if (e.target.classList.contains('save-btn')) {
            const card = e.target.closest('.border');
            const projectId = card.dataset.projectId;
            const status = card.querySelector('select').value;
            const review = card.querySelector('textarea').value;
            
            e.target.textContent = 'Saving...';
            e.target.disabled = true;

            const response = await fetch('../php/admin/update_project_status.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ projectId, status, review })
            });

            if(response.ok) {
                alert('Project updated successfully!');
                card.querySelector('.status-text').textContent = status;
            } else {
                alert('Failed to update project.');
            }
            e.target.textContent = 'Save';
            e.target.disabled = false;
        }
    });

    fetchAdminProjects();
});