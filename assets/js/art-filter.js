document.addEventListener('DOMContentLoaded', function() {
    const cardViewBtn = document.getElementById('cardViewBtn');
    const tableViewBtn = document.getElementById('tableViewBtn');
    const cardView = document.getElementById('cardView');
    const tableView = document.getElementById('tableView');
    const searchInput = document.getElementById('searchInput');
    const categoryFilter = document.getElementById('categoryFilter');

    // Normalize category strings for comparison
    function normalizeCategory(category) {
        if (!category) return '';
        // Convert to lowercase and replace spaces/dashes with underscore
        return category.toLowerCase().replace(/[\s-]/g, '_');
    }

    // View toggle functionality
    cardViewBtn.addEventListener('click', function() {
        cardView.style.display = 'flex';
        tableView.style.display = 'none';
        cardViewBtn.classList.add('active');
        tableViewBtn.classList.remove('active');
        localStorage.setItem('preferredView', 'card');
    });

    tableViewBtn.addEventListener('click', function() {
        cardView.style.display = 'none';
        tableView.style.display = 'block';
        tableViewBtn.classList.add('active');
        cardViewBtn.classList.remove('active');
        localStorage.setItem('preferredView', 'table');
    });

    // Restore preferred view
    const preferredView = localStorage.getItem('preferredView');
    if (preferredView === 'table') {
        tableViewBtn.click();
    }

    // Combined search and filter functionality
    function filterItems() {
        const searchTerm = searchInput.value.toLowerCase();
        const selectedCategory = categoryFilter.value;
        const normalizedSelectedCategory = normalizeCategory(selectedCategory);

        function shouldShowItem(titleText, artistText, category) {
            const matchesSearch = !searchTerm || 
                                titleText.toLowerCase().includes(searchTerm) || 
                                artistText.toLowerCase().includes(searchTerm);
            
            // Compare normalized categories
            const normalizedItemCategory = normalizeCategory(category);
            const matchesCategory = !selectedCategory || 
                                  normalizedItemCategory === normalizedSelectedCategory;
            
            return matchesSearch && matchesCategory;
        }

        // Filter cards
        const cards = document.querySelectorAll('.artwork-item');
        let visibleCards = 0;
        cards.forEach(card => {
            const title = card.querySelector('.card-title').textContent;
            const artist = card.querySelector('.artist-info span').textContent;
            const category = card.getAttribute('data-category');
            const shouldShow = shouldShowItem(title, artist, category);
            card.style.display = shouldShow ? '' : 'none';
            if (shouldShow) visibleCards++;
        });

        // Filter table rows
        const rows = document.querySelectorAll('#tableView tbody tr');
        let visibleRows = 0;
        rows.forEach(row => {
            if (!row.classList.contains('no-results')) {
                const title = row.querySelector('td:nth-child(2)').textContent;
                const artist = row.querySelector('td:nth-child(3)').textContent;
                const category = row.getAttribute('data-category');
                const shouldShow = shouldShowItem(title, artist, category);
                row.style.display = shouldShow ? '' : 'none';
                if (shouldShow) visibleRows++;
            }
        });

        // Show/hide no results message for current view
        const currentView = cardView.style.display !== 'none' ? cardView : tableView;
        const noResultsElement = currentView.querySelector('.no-results');
        
        if (noResultsElement) {
            const hasVisibleItems = currentView === cardView ? visibleCards > 0 : visibleRows > 0;
            noResultsElement.style.display = hasVisibleItems ? 'none' : '';
        }
    }

    // Add event listeners
    searchInput.addEventListener('input', filterItems);
    categoryFilter.addEventListener('change', filterItems);
});