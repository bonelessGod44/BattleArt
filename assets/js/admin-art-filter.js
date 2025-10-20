document.addEventListener('DOMContentLoaded', function() {
    // View toggle functionality
    const cardViewBtn = document.getElementById('cardViewBtn');
    const tableViewBtn = document.getElementById('tableViewBtn');
    const cardView = document.getElementById('cardView');
    const tableView = document.getElementById('tableView');

    if (cardViewBtn && tableViewBtn) {
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

        // Restore user's preferred view
        const preferredView = localStorage.getItem('preferredView');
        if (preferredView === 'table') {
            tableViewBtn.click();
        }
    }

    // Combined search and filter functionality
    function filterItems() {
        const searchTerm = document.getElementById('searchInput').value.toLowerCase();
        const category = document.getElementById('categoryFilter').value.toLowerCase();
        
        function matchesCriteria(element, type) {
            let title, artist, itemCategory;
            
            if (type === 'card') {
                title = element.querySelector('.card-title')?.textContent || '';
                artist = element.querySelector('.artist-name')?.textContent || '';
                itemCategory = element.dataset.category || '';
            } else {
                const cells = element.querySelectorAll('td');
                title = cells[1]?.textContent || '';
                artist = cells[2]?.textContent || '';
                itemCategory = cells[3]?.textContent || '';
            }

            const matchesSearch = !searchTerm || 
                                title.toLowerCase().includes(searchTerm) || 
                                artist.toLowerCase().includes(searchTerm);
            const matchesCategory = !category || itemCategory.toLowerCase() === category;
            
            return matchesSearch && matchesCategory;
        }

        // Filter Grid View
        const cards = document.querySelectorAll('.art-card');
        let visibleCards = 0;
        cards.forEach(card => {
            const shouldShow = matchesCriteria(card, 'card');
            card.closest('.col-md-4').style.display = shouldShow ? '' : 'none';
            if (shouldShow) visibleCards++;
        });

        // Filter Table View
        const rows = document.querySelectorAll('#tableView tbody tr');
        let visibleRows = 0;
        rows.forEach(row => {
            const shouldShow = matchesCriteria(row, 'table');
            row.style.display = shouldShow ? '' : 'none';
            if (shouldShow) visibleRows++;
        });

        // Show/hide no results message
        const noResults = document.querySelector('.no-results');
        if (noResults) {
            if (cardView.style.display !== 'none') {
                noResults.style.display = visibleCards === 0 ? 'block' : 'none';
            } else {
                noResults.style.display = visibleRows === 0 ? 'block' : 'none';
            }
        }

        // Update counter if it exists
        const counter = document.querySelector('.results-counter');
        if (counter) {
            const count = cardView.style.display !== 'none' ? visibleCards : visibleRows;
            counter.textContent = `Showing ${count} artwork${count !== 1 ? 's' : ''}`;
        }
    }

    // Add event listeners for search and category filter
    const searchInput = document.getElementById('searchInput');
    const categoryFilter = document.getElementById('categoryFilter');

    if (searchInput) {
        searchInput.addEventListener('input', filterItems);
    }
    if (categoryFilter) {
        categoryFilter.addEventListener('change', filterItems);
    }
});