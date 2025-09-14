
document.addEventListener('DOMContentLoaded', function() {
    // Existing booking modal logic...
    // ...existing code...

    // Quote Request PREVIEW logic (no AJAX yet)
    const quoteForms = document.querySelectorAll('.quote-request-form');
    const quoteModal = document.getElementById('quoteRequestModal');
    const closeQuoteModalBtn = quoteModal.querySelector('.close-modal-btn');
    const previewProjectDescription = document.getElementById('previewProjectDescription');
    const previewUploadPhotos = document.getElementById('previewUploadPhotos');
    const previewFileList = document.getElementById('previewFileList');
    const quotePreviewForm = document.getElementById('quotePreviewForm');

    function showQuoteModal(initialDescription = '', initialFiles = []) {
        previewProjectDescription.value = initialDescription;
        previewUploadPhotos.value = '';
        previewFileList.innerHTML = '';
        quoteModal.classList.add('active');
    }
    function closeQuoteModal() {
        quoteModal.classList.remove('active');
        quoteModal.style.display = 'none';
    }
    closeQuoteModalBtn.addEventListener('click', closeQuoteModal);
    window.addEventListener('click', function(e) {
        if (e.target === quoteModal) closeQuoteModal();
    });

    previewUploadPhotos.addEventListener('change', function() {
        const files = previewUploadPhotos.files;
        previewFileList.innerHTML = '';
        if (files.length > 0) {
            let html = '<strong>Selected Photos:</strong><ul style="margin:0.5rem 0 0 1rem;">';
            for (let i = 0; i < files.length; i++) {
                html += `<li>${files[i].name}</li>`;
            }
            html += '</ul>';
            previewFileList.innerHTML = html;
        }
    });

    let selectedProviderId = null;
    let selectedServiceType = '';
    let selectedSubcategory = '';

    function closeAllModals() {
        document.getElementById('bookingModal').classList.remove('active');
        document.getElementById('bookingModal').style.display = 'none';
        document.getElementById('quoteRequestModal').classList.remove('active');
        document.getElementById('quoteRequestModal').style.display = 'none';
    }

    quoteForms.forEach(form => {
        const btn = form.querySelector('.btn-request-quote');
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            closeAllModals();
            document.getElementById('quoteRequestModal').style.display = 'block';
            selectedProviderId = form.getAttribute('data-provider-id');
            selectedServiceType = form.getAttribute('data-service-type');
            selectedSubcategory = form.getAttribute('data-subcategory');
            showQuoteModal('');
        });
    });

    quotePreviewForm.addEventListener('submit', function(e) {
        console.log('Submitting quotation request...');
        e.preventDefault();
        const projectDescription = previewProjectDescription.value;
        // Prepare form data
        const formData = new FormData();
        formData.append('provider_id', selectedProviderId);
        formData.append('service_type', selectedServiceType);
        formData.append('subcategory', selectedSubcategory);
        formData.append('project_description', projectDescription);
        // Add files
        for (let i = 0; i < previewUploadPhotos.files.length; i++) {
            formData.append('photos[]', previewUploadPhotos.files[i]);
        }
        // AJAX submit
        fetch('../handlers/handle_quote_request.php', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            closeQuoteModal();
            if (data.success) {
                alert('Quotation request submitted!');
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(() => {
            closeQuoteModal();
            alert('Could not send request. Please try again.');
        });
    });

    // Real-time calendar rendering
    function renderRealTimeCalendar(providerId, year, month) {
        const calendarContainer = document.getElementById('calendar-container');
        calendarContainer.innerHTML = '';
        document.getElementById('time-slots-section').style.display = 'none';
        fetch('../provider/get_provider_availability.php?provider_id=' + providerId)
            .then(response => response.json())
            .then(data => {
                console.log('Provider availability response:', data);
                const availability = data.availability || {};
                const availableDates = Object.keys(availability);
                // Calendar header and grid in one container for perfect alignment
                let html = '<div style="display:grid;grid-template-columns:repeat(7,1fr);gap:2px;background:#fff;border-radius:8px;padding:8px;">';
                // Weekday headers
                const weekdays = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
                for (let wd = 0; wd < 7; wd++) {
                    html += `<div style='text-align:center;color:#374151;font-weight:600;font-size:1.1rem;background:#f9fafb;border-radius:6px;height:32px;display:flex;align-items:center;justify-content:center;'>${weekdays[wd]}</div>`;
                }
                // Calculate offset for Monday as first day
                let jsFirstDay = new Date(year, month, 1).getDay();
                let offset = (jsFirstDay + 6) % 7;
                for (let i = 0; i < offset; i++) html += '<div></div>';
                const daysInMonth = new Date(year, month+1, 0).getDate();
                for (let d = 1; d <= daysInMonth; d++) {
                    const dateStr = year + '-' + String(month+1).padStart(2,'0') + '-' + String(d).padStart(2,'0');
                    const isAvailable = availableDates.includes(dateStr);
                    html += `<div class='calendar-date-cell' data-date='${dateStr}' style="height:40px;width:40px;display:flex;align-items:center;justify-content:center;border-radius:6px;font-size:1rem;font-weight:500;margin:2px;cursor:${isAvailable ? 'pointer' : 'not-allowed'};${isAvailable ? 'background:#34a853;color:#fff;' : 'background:#f1f5f9;color:#374151;'}">${d}</div>`;
                }
                html += '</div>';
                calendarContainer.innerHTML = html;
                // Month title
                const monthNames = ["January","February","March","April","May","June","July","August","September","October","November","December"];
                document.getElementById('calendarMonthTitle').textContent = monthNames[month] + ' ' + year;
                // Add click event for available dates
                document.querySelectorAll('.calendar-date-cell').forEach(cell => {
                    cell.addEventListener('click', function() {
                        if (cell.style.background === 'rgb(52, 168, 83)' || cell.style.background === '#34a853') {
                            // Show available times for this date
                            const date = cell.getAttribute('data-date');
                            const times = availability[date] || [];
                            const timeSlotsList = document.getElementById('time-slots-list');
                            timeSlotsList.innerHTML = '';
                            if (times.length === 0) {
                                timeSlotsList.innerHTML = '<em>No times available for this date.</em>';
                            } else {
                                times.forEach(time => {
                                    const btn = document.createElement('button');
                                    btn.className = 'btn btn-outline-primary time-slot-btn';
                                    btn.textContent = time;
                                    btn.onclick = function() {
                                        // Highlight selected
                                        document.querySelectorAll('.time-slot-btn').forEach(b => b.classList.remove('selected'));
                                        btn.classList.add('selected');
                                        // Show payment form as popup/modal
                                        var paymentStep = document.getElementById('paymentStep');
                                        var calendarStep = document.getElementById('calendarStep');
                                        if (paymentStep) paymentStep.style.display = 'block';
                                        if (calendarStep) calendarStep.style.display = 'none';
                                    };
                                    timeSlotsList.appendChild(btn);
                                });
                            }
                            document.getElementById('time-slots-section').style.display = '';
                        } else {
                            // Show not available message
                            alert('This date is not available for this provider.');
                        }
                    });
                });
            });
    }
    // Month navigation
    let currentYear = new Date().getFullYear();
    let currentMonth = new Date().getMonth();
    function updateMonthTitle(year, month) {
        const monthNames = ["January","February","March","April","May","June","July","August","September","October","November","December"];
        document.getElementById('calendarMonthTitle').textContent = monthNames[month] + ' ' + year;
    }
    const consultBtns = document.querySelectorAll('.btn-book-consultation');
    consultBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            closeAllModals();
            // Reset modal content
            var calendarStep = document.getElementById('calendarStep');
            var paymentStep = document.getElementById('paymentStep');
            var timeSlotsSection = document.getElementById('time-slots-section');
            var bookingModal = document.getElementById('bookingModal');
            if (calendarStep) calendarStep.style.display = '';
            if (paymentStep) paymentStep.style.display = 'none';
            if (timeSlotsSection) timeSlotsSection.style.display = 'none';
            if (bookingModal) {
                bookingModal.classList.add('active');
                bookingModal.style.display = 'block';
            }
            const providerId = btn.closest('.provider-actions').querySelector('.quote-request-form').getAttribute('data-provider-id');
            console.log('Book Consultation clicked. Provider ID:', providerId);
            renderRealTimeCalendar(providerId, currentYear, currentMonth);
            updateMonthTitle(currentYear, currentMonth);
            document.getElementById('prevMonthBtn').onclick = function() {
                currentMonth--;
                if (currentMonth < 0) { currentMonth = 11; currentYear--; }
                renderRealTimeCalendar(providerId, currentYear, currentMonth);
                updateMonthTitle(currentYear, currentMonth);
            };
            document.getElementById('nextMonthBtn').onclick = function() {
                currentMonth++;
                if (currentMonth > 11) { currentMonth = 0; currentYear++; }
                renderRealTimeCalendar(providerId, currentYear, currentMonth);
                updateMonthTitle(currentYear, currentMonth);
            };
        });
    });

    // Ensure close button works for booking modal
    const bookingModal = document.getElementById('bookingModal');
    const closeBookingModalBtn = bookingModal.querySelector('.close-modal-btn');
    closeBookingModalBtn.addEventListener('click', function() {
        bookingModal.classList.remove('active');
        bookingModal.style.display = 'none';
    });
    window.addEventListener('click', function(e) {
        if (e.target === bookingModal) {
            bookingModal.classList.remove('active');
            bookingModal.style.display = 'none';
        }
    });
});
