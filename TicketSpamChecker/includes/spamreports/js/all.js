function toggleAll(source) {
    const checkboxes = document.querySelectorAll('input[name="selected_reports[]"]');
    checkboxes.forEach((checkbox) => {
        checkbox.checked = source.checked;
    });
    toggleBulkDeleteButton();
}

function toggleBulkDeleteButton() {
    const selected = document.querySelectorAll('input[name="selected_reports[]"]:checked').length;
    const btn = document.getElementById('bulkDeleteBtn');
    if (btn) {
        btn.style.display = selected > 1 ? 'block' : 'none';
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const checkboxes = document.querySelectorAll('input[name="selected_reports[]"]');
    checkboxes.forEach(cb => {
        cb.addEventListener('change', toggleBulkDeleteButton);
    });

    const spamCount = window.spamCount || 0;
    const legitCount = window.legitCount || 0;

    let spamData;
    if (spamCount === 0 && legitCount === 0) {
        spamData = {
            labels: ['Spam', 'Legit'],
            datasets: [{
                data: [1, 1],
                backgroundColor: ['#EF4444', '#10B981']
            }]
        };
    } else {
        spamData = {
            labels: ['Spam', 'Legit'],
            datasets: [{
                data: [spamCount, legitCount],
                backgroundColor: ['#EF4444', '#10B981']
            }]
        };
    }

    new Chart(document.getElementById('spamChart'), {
        type: 'doughnut',
        data: spamData,
        options: {
            responsive: true,
            plugins: {
                legend: {
                    labels: { color: 'white' }
                },
                tooltip: {
                    callbacks: {
                        label: function (context) {
                            if (spamCount === 0 && legitCount === 0) {
                                return 'Totaal: 0';
                            }
                            return context.label + ': ' + context.formattedValue;
                        }
                    }
                }
            }
        }
    });
});