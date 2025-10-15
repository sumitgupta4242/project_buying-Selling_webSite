document.addEventListener('DOMContentLoaded', () => {
    const formWrapper = document.getElementById('form-wrapper');
    const urlParams = new URLSearchParams(window.location.search);
    const projectId = urlParams.get('id');

    if (!projectId) {
        formWrapper.innerHTML = '<p class="text-red-500 text-center p-8">Error: No project ID provided.</p>';
        return;
    }

    const formHtml = `
        <form id="projectForm" enctype="multipart/form-data">
            <input type="hidden" id="project-id" name="project-id">
            <div class="mb-6">
                <label for="project-title" class="block text-sm font-bold text-gray-700 mb-1">Project Title</label>
                <input type="text" id="project-title" name="project-title" class="block w-full px-3 py-2 bg-gray-50 border border-gray-300 rounded-md" required>
            </div>
            <div class="mb-6">
                <label for="project-subject" class="block text-sm font-bold text-gray-700 mb-1">Project Subject</label>
                <select id="project-subject" name="project-subject" class="block w-full px-3 py-2 bg-gray-50 border border-gray-300 rounded-md" required>
                    <option value="web-development">Web Development</option>
                    <option value="mobile-app">Mobile App Development</option>
                    <option value="machine-learning">Machine Learning & AI</option>
                    <option value="data-science">Data Science</option>
                    <option value="game-development">Game Development</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <div class="mb-6">
                <label class="block text-sm font-bold text-gray-700 mb-1">Project Price Range (₹ INR)</label>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="min-price" class="block text-xs font-medium text-gray-600">Minimum Price</label>
                        <input type="number" id="min-price" name="min-price" class="mt-1 block w-full px-3 py-2 bg-gray-50 border border-gray-300 rounded-md" min="0" required>
                    </div>
                    <div>
                        <label for="max-price" class="block text-xs font-medium text-gray-600">Maximum Price</label>
                        <input type="number" id="max-price" name="max-price" class="mt-1 block w-full px-3 py-2 bg-gray-50 border border-gray-300 rounded-md" min="0" required>
                    </div>
                </div>
            </div>
            <div class="mb-6">
                <label for="project-description" class="block text-sm font-bold text-gray-700 mb-1">Project Description</label>
                <textarea id="project-description" name="project-description" rows="5" class="block w-full px-3 py-2 bg-gray-50 border border-gray-300 rounded-md" required></textarea>
            </div>
             <div class="mb-6">
                <label for="video-link" class="block text-sm font-bold text-gray-700 mb-1">Demo Video Link (Optional)</label>
                <input type="url" id="video-link" name="video-link" class="block w-full px-3 py-2 bg-gray-50 border border-gray-300 rounded-md">
            </div>
            <div class="mb-8">
                <label for="how-to-run" class="block text-sm font-bold text-gray-700 mb-1">How to Run This Project</label>
                <textarea id="how-to-run" name="how-to-run" rows="5" class="block w-full px-3 py-2 bg-gray-50 border border-gray-300 rounded-md" required></textarea>
            </div>
            <div class="mb-8">
                <label for="seller-notes" class="block text-sm font-bold text-gray-700 mb-1">Notes to Admin (Optional)</label>
                <textarea id="seller-notes" name="seller-notes" rows="4" class="block w-full px-3 py-2 bg-gray-50 border border-gray-300 rounded-md"></textarea>
            </div>
             <p class="text-sm text-gray-500 mb-4">Note: File uploads cannot be changed during an edit. Please ensure your original files are correct.</p>
            <div class="text-center">
                <button type="submit" id="update-btn" class="w-full md:w-1/2 bg-indigo-600 text-white font-bold py-3 px-6 rounded-lg hover:bg-indigo-700">
                    Update Project
                </button>
            </div>
        </form>
    `;

    const populateForm = (project) => {
        formWrapper.innerHTML = formHtml;
        document.getElementById('project-id').value = project.id;
        document.getElementById('project-title').value = project.title;
        document.getElementById('project-subject').value = project.subject;
        document.getElementById('min-price').value = project.min_price;
        document.getElementById('max-price').value = project.max_price;
        document.getElementById('project-description').value = project.description;
        document.getElementById('video-link').value = project.video_link || '';
        document.getElementById('how-to-run').value = project.how_to_run_text;
        document.getElementById('seller-notes').value = project.seller_notes || '';
        
        const projectForm = document.getElementById('projectForm');
        projectForm.addEventListener('submit', handleUpdateSubmit);
    };

    const fetchProjectDetails = async () => {
        try {
            const response = await fetch(`php/project/get_project_details.php?id=${projectId}`);
            if (!response.ok) {
                const error = await response.json();
                throw new Error(error.message);
            }
            const data = await response.json();
            if (data.success) {
                populateForm(data.project);
            } else {
                throw new Error(data.message);
            }
        } catch (error) {
            formWrapper.innerHTML = `<p class="text-red-500 text-center p-8">Error: ${error.message}</p>`;
        }
    };

    const handleUpdateSubmit = async (e) => {
        e.preventDefault();
        const updateBtn = document.getElementById('update-btn');
        updateBtn.textContent = 'Updating...';
        updateBtn.disabled = true;

        const formData = new FormData(e.target);
        
        try {
            const response = await fetch('php/project/update_project.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            alert(result.message);
            if (result.success) {
                window.location.href = 'dashboard/';
            }
        } catch (error) {
            console.error('Update failed:', error);
            alert('An error occurred while updating.');
        } finally {
            updateBtn.textContent = 'Update Project';
            updateBtn.disabled = false;
        }
    };

    fetchProjectDetails();
});