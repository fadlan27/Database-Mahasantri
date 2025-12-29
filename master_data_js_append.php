
// Toggle Filter Visibility
function toggleFilters() {
    const content = document.getElementById('filterContent');
    const icon = document.getElementById('filterIcon');
    const header = document.getElementById('filterHeader');
    
    if (content.classList.contains('hidden')) {
        // Show
        content.classList.remove('hidden');
        icon.style.transform = 'rotate(0deg)';
        header.classList.add('border-transparent');
        header.classList.remove('border-slate-100');
    } else {
        // Hide
        content.classList.add('hidden');
        icon.style.transform = 'rotate(180deg)';
        header.classList.remove('border-transparent');
        header.classList.add('border-slate-100');
    }
}
