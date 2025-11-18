document.addEventListener('DOMContentLoaded', () => {
    const table = document.getElementById('market-table');
    const lastRefresh = document.getElementById('last-refresh');

    if (!table) {
        return;
    }

    const updateRows = (updates) => {
        updates.forEach((stock) => {
            const row = table.querySelector(`tr[data-stock-id="${stock.id}"]`);
            if (row) {
                const priceCell = row.querySelector('.price');
                const updatedCell = row.querySelector('.updated');
                if (priceCell) {
                    priceCell.textContent = `$${parseFloat(stock.price).toFixed(2)}`;
                }
                if (updatedCell) {
                    updatedCell.textContent = stock.last_updated;
                }
            }
        });
        if (lastRefresh) {
            lastRefresh.textContent = new Date().toLocaleTimeString();
        }
    };

    const fetchUpdates = () => {
        fetch('auto_update.php', {
            method: 'POST',
            headers: {'X-Requested-With': 'XMLHttpRequest'},
            credentials: 'same-origin'
        })
            .then((response) => {
                if (!response.ok) {
                    throw new Error('Network response was not ok.');
                }
                return response.json();
            })
            .then((data) => {
                if (data.success) {
                    updateRows(data.updated || []);
                }
            })
            .catch((error) => {
                console.error('Price update failed:', error);
            });
    };

    fetchUpdates();
    setInterval(fetchUpdates, 15000);
});

