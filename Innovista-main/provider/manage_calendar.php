<?php 
$pageTitle = 'Manage Calendar';
require_once 'provider_header.php'; 
?>

<div class="booking-layout">
    <div class="booking-card">
        <div class="booking-header">Confirm Date & Time</div>
        <div class="booking-content">
            <!-- Calendar Panel -->
            <div class="calendar-panel">
                <div class="calendar-header">
                    <button id="prev-month" class="calendar-arrow">&#8592;</button>
                    <span class="calendar-title" id="calendar-title"></span>
                    <button id="next-month" class="calendar-arrow">&#8594;</button>
                </div>
                <div class="calendar-card">
                    <div class="calendar-grid-header">
                        <span>Mon</span><span>Tue</span><span>Wed</span><span>Thu</span>
                        <span>Fri</span><span>Sat</span><span>Sun</span>
                    </div>
                    <div id="calendar-days" class="calendar-days"></div>
                </div>
            </div>

            <!-- Time Panel -->
            <div class="time-panel">
                <div class="time-header">Select Time</div>
                <div class="selected-time-display" id="selected-time-display">Selected Time: None</div>
                <div class="time-slots" id="time-slots"></div>
                <button class="save-btn" id="save-btn">Confirm</button>
            </div>
        </div>
    </div>
</div>
<link rel="stylesheet" href="../public/assets/css/manage_calendar.css">

<script>
const timeSlots = [
    "09:00 AM","09:30 AM","10:00 AM","10:30 AM",
    "11:00 AM","11:30 AM","12:00 PM","12:30 PM",
    "01:00 PM","01:30 PM","02:00 PM","02:30 PM",
    "03:00 PM","03:30 PM","04:00 PM","04:30 PM",
    "05:00 PM","05:30 PM","06:00 PM"
];



// Store selected times for each date: { 'YYYY-MM-DD': ["09:00 AM", ...] }
let selectedAvailability = {};
let selectedDate = null;
let savedAvailability = {}; // fetched from backend

function renderTimeSlots() {
    const container = document.getElementById('time-slots');
    const selectedDisplay = document.getElementById('selected-time-display');
    container.innerHTML = '';
    if (!selectedDate) {
        selectedDisplay.textContent = 'Selected Date: None';
        return;
    }
    selectedDisplay.textContent = `Selected Date: ${selectedDate}`;
    const selectedTimes = selectedAvailability[selectedDate] || [];
    const dbTimes = savedAvailability[selectedDate] || [];
    timeSlots.forEach(slot => {
        const div = document.createElement('div');
        div.classList.add('time-slot');
        div.textContent = slot;
        if (dbTimes.includes(slot)) div.classList.add('selected-db');
        if (selectedTimes.includes(slot)) div.classList.add('selected');
        div.addEventListener('click', () => {
            let times = selectedAvailability[selectedDate] || [];
            if (times.includes(slot)) {
                times = times.filter(t => t !== slot);
            } else {
                times.push(slot);
            }
            selectedAvailability[selectedDate] = times;
            renderTimeSlots();
        });
        container.appendChild(div);
    });
}


function renderCalendar(year, month) {
    const calendarDays = document.getElementById('calendar-days');
    const calendarTitle = document.getElementById('calendar-title');
    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);
    const startDay = (firstDay.getDay() + 6) % 7;
    const daysInMonth = lastDay.getDate();

    calendarTitle.textContent = firstDay.toLocaleString('default', { month: 'long', year: 'numeric' });
    calendarDays.innerHTML = '';

    for(let i=0;i<startDay;i++){ calendarDays.innerHTML += '<div></div>'; }
    for(let i=1;i<=daysInMonth;i++){
        const dayDiv = document.createElement('div');
        dayDiv.classList.add('calendar-day');
        dayDiv.textContent = i;
        const today = new Date();
        const dateStr = `${firstDay.getFullYear()}-${String(firstDay.getMonth()+1).padStart(2,'0')}-${String(i).padStart(2,'0')}`;
        if(i===today.getDate() && month===today.getMonth() && year===today.getFullYear()){
            dayDiv.classList.add('today');
        }
        // Highlight if available in DB or selected
        if ((savedAvailability[dateStr] && savedAvailability[dateStr].length > 0) || (selectedAvailability[dateStr] && selectedAvailability[dateStr].length > 0)) {
            dayDiv.classList.add('selected');
        }
        dayDiv.addEventListener('click', () => {
            selectedDate = dateStr;
            renderCalendar(year, month);
            renderTimeSlots();
        });
        if (selectedDate === dateStr) {
            dayDiv.classList.add('selected');
        }
        calendarDays.appendChild(dayDiv);
    }
}


document.addEventListener('DOMContentLoaded', () => {
    const today = new Date();
    selectedDate = `${today.getFullYear()}-${String(today.getMonth()+1).padStart(2,'0')}-${String(today.getDate()).padStart(2,'0')}`;

    // Always render calendar and time slots, even if AJAX fails
    function renderAll() {
        renderCalendar(today.getFullYear(), today.getMonth());
        renderTimeSlots();
        if (typeof renderSavedAvailability === 'function') renderSavedAvailability();
    }

    // Fetch saved availability from backend first
    fetch('get_availability.php')
        .then(res => {
            if (!res.ok) throw new Error('Network response was not ok');
            return res.json();
        })
        .then(data => {
            savedAvailability = (data && data.success && data.availability) ? data.availability : {};
            renderAll();
        })
        .catch(err => {
            console.error('Error fetching availability:', err);
            savedAvailability = {};
            renderAll();
        });

    document.getElementById('prev-month').addEventListener('click', () => {
        today.setMonth(today.getMonth() - 1);
        renderCalendar(today.getFullYear(), today.getMonth());
        renderTimeSlots();
    });
    document.getElementById('next-month').addEventListener('click', () => {
        today.setMonth(today.getMonth() + 1);
        renderCalendar(today.getFullYear(), today.getMonth());
        renderTimeSlots();
    });

    document.getElementById('save-btn').addEventListener('click', () => {
        // Prepare array of {date, times}
        const availabilityArr = Object.entries(selectedAvailability)
            .filter(([date, times]) => times.length > 0)
            .map(([date, times]) => ({ date, times }));
        if (availabilityArr.length === 0) {
            alert('Please select at least one date and time.');
            return;
        }
        fetch('save_availability.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ availability: availabilityArr })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert('Availability saved successfully!');
                selectedAvailability = {};
                // Refresh saved availability from backend
                fetch('get_availability.php')
                    .then(res => res.json())
                    .then(data => {
                        savedAvailability = (data && data.success && data.availability) ? data.availability : {};
                        renderAll();
                    })
                    .catch(err => {
                        console.error('Error fetching availability after save:', err);
                        renderAll();
                    });
            } else {
                alert('Error saving availability. ' + (data.error || ''));
            }
        })
        .catch(() => alert('Error saving availability.'));
    });
});

/* Highlight saved (DB) times in time slots */



// --- Saved Availability Section ---
function renderSavedAvailability() {
    fetch('get_availability.php')
        .then(res => res.json())
        .then(data => {
            const container = document.getElementById('saved-availability-list');
            container.innerHTML = '';
            if (!data.success || !data.availability || Object.keys(data.availability).length === 0) {
                container.innerHTML = '<em>No saved availability.</em>';
                return;
            }
            Object.entries(data.availability).forEach(([date, times]) => {
                const dateDiv = document.createElement('div');
                dateDiv.className = 'saved-date';
                dateDiv.textContent = date;
                times.forEach(time => {
                    const timeDiv = document.createElement('span');
                    timeDiv.className = 'saved-time';
                    timeDiv.textContent = time;
                    // Add delete button
                    const delBtn = document.createElement('button');
                    delBtn.className = 'delete-time-btn';
                    delBtn.textContent = '×';
                    delBtn.title = 'Delete this time';
                    delBtn.onclick = function() {
                        if (confirm(`Delete ${date} ${time}?`)) {
                            deleteAvailability(date, time);
                        }
                    };
                    timeDiv.appendChild(delBtn);
                    dateDiv.appendChild(timeDiv);
                });
                container.appendChild(dateDiv);
            });
        });
}

function deleteAvailability(date, time) {
    fetch('delete_availability.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ date, time })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            renderSavedAvailability();
        } else {
            alert('Error deleting: ' + (data.error || ''));
        }
    })
    .catch(() => alert('Error deleting availability.'));
}
</script>

<div class="saved-availability-section">
    <h3>Saved Availability</h3>
    <div id="saved-availability-list"></div>
</div>



<?php require_once 'provider_footer.php'; ?>