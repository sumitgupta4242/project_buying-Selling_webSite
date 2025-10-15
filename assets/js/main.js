document.addEventListener('DOMContentLoaded', function() {
    
    // Initialize all features
    initLiveSearch();
    initReviewForm();
    initHeroSlider();

});

// --- 1. LIVE SEARCH LOGIC ---
function initLiveSearch() {
    const searchInput = document.getElementById('search-input');
    const suggestionsBox = document.getElementById('suggestions-box');
    
    if (!searchInput) return; // Exit if the search input isn't on this page

    // Listen for the 'input' event, which fires every time the user types
    searchInput.addEventListener('input', function() {
        const query = this.value;

        if (query.length < 2) {
            suggestionsBox.innerHTML = '';
            suggestionsBox.style.display = 'none'; // Hide the box
            return;
        }

        fetch(`live_search.php?query=${encodeURIComponent(query)}`)
            .then(response => response.json())
            .then(suggestions => {
                if (suggestions.length === 0) {
                    suggestionsBox.innerHTML = '';
                    suggestionsBox.style.display = 'none';
                    return;
                }

                let suggestionHTML = '';
                suggestions.forEach(suggestion => {
                    suggestionHTML += `<a href="#" class="list-group-item list-group-item-action">${suggestion}</a>`;
                });

                suggestionsBox.innerHTML = suggestionHTML;
                suggestionsBox.style.display = 'block'; // Show the box
            })
            .catch(error => console.error('Error fetching suggestions:', error));
    });

    // --- IMPROVEMENT: Using event delegation for suggestion clicks ---
    suggestionsBox.addEventListener('click', function(e) {
        if (e.target && e.target.matches('a.list-group-item')) {
            e.preventDefault();
            searchInput.value = e.target.textContent;
            suggestionsBox.innerHTML = '';
            suggestionsBox.style.display = 'none';
            searchInput.form.submit();
        }
    });

    // Hide suggestions if the user clicks anywhere else
    document.addEventListener('click', function(e) {
        if (e.target !== searchInput) {
            suggestionsBox.innerHTML = '';
            suggestionsBox.style.display = 'none';
        }
    });
}


// --- 2. REVIEW FORM LOGIC ---
function initReviewForm() {
    const reviewForm = document.getElementById('review-form');
    if (!reviewForm) return; // Exit if the form isn't on this page

    reviewForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const reviewData = {
            project_id: formData.get('project_id'),
            rating: formData.get('rating'),
            review_text: formData.get('review_text')
        };

        if (!reviewData.rating) {
            alert('Please select a star rating.');
            return;
        }

        fetch('submit_review.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(reviewData)
        })
        .then(response => response.json())
        .then(data => {
            const messageDiv = document.getElementById('review-message');
            if (data.status === 'success') {
                messageDiv.innerHTML = `<div class="alert alert-success">${data.message}</div>`;
                reviewForm.style.display = 'none';
            } else {
                messageDiv.innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
            }
        });
    });
}


// --- 3. HOMEPAGE HERO SLIDER LOGIC ---
function initHeroSlider() {
    const slides = document.querySelectorAll('.hero-slide');
    if (slides.length <= 1) return; // Only run if there's more than one image

    let currentSlide = 0;
    
    setInterval(() => {
        slides[currentSlide].classList.remove('active');
        currentSlide = (currentSlide + 1) % slides.length;
        slides[currentSlide].classList.add('active');
    }, 5000); // 5 seconds
}