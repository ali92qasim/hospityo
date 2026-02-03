// Simple test function to verify jQuery is working
function testDateTimePicker() {
    console.log('Testing datetime picker...');
    console.log('jQuery available:', typeof $ !== 'undefined');
    console.log('Datetime picker elements found:', $('.datetime-picker').length);
}

// Wait for both jQuery and DOM to be ready
function initDateTimePicker() {
    console.log('Initializing datetime picker...');
    
    // Test if jQuery is available
    if (typeof $ === 'undefined') {
        console.error('jQuery is not loaded!');
        return;
    }
    
    // Use event delegation for dynamically added elements
    $(document).off('click.datetime-picker').on('click.datetime-picker', '.datetime-picker', function(e) {
        e.preventDefault();
        e.stopPropagation();
        console.log('Datetime picker clicked');
        showDateTimePicker($(this));
    });
    
    // Also try direct binding for existing elements
    $('.datetime-picker').off('click.direct').on('click.direct', function(e) {
        e.preventDefault();
        e.stopPropagation();
        console.log('Direct datetime picker clicked');
        showDateTimePicker($(this));
    });
    
    // Close picker when clicking outside
    $(document).off('click.datetime-close').on('click.datetime-close', function(e) {
        if (!$(e.target).closest('.datetime-picker-container, .datetime-picker-popup').length) {
            $('.datetime-picker-popup').remove();
        }
    });
    
    console.log('Datetime picker initialized for', $('.datetime-picker').length, 'elements');
}

function showDateTimePicker($input) {
    console.log('Showing datetime picker for:', $input);
    
    // Remove existing popups
    $('.datetime-picker-popup').remove();
    
    // Ensure input has a container
    if (!$input.parent().hasClass('datetime-picker-container')) {
        $input.wrap('<div class="datetime-picker-container" style="position: relative;"></div>');
    }
    
    const currentValue = $input.val();
    let selectedDate = new Date();
    let selectedTime = '09:00';
    
    if (currentValue) {
        const parts = currentValue.split(' ');
        if (parts.length === 2) {
            selectedDate = new Date(parts[0]);
            selectedTime = parts[1];
        }
    }
    
    const $popup = $(`
        <div class="datetime-picker-popup">
            <div class="datetime-grid">
                <div>
                    <div class="datetime-picker-header">Select Date</div>
                    <input type="date" class="date-input w-full px-3 py-2 border border-gray-300 rounded-lg" 
                           value="${selectedDate.toISOString().split('T')[0]}" min="${new Date().toISOString().split('T')[0]}">
                </div>
                <div>
                    <div class="datetime-picker-header">Select Time</div>
                    <div class="time-grid">
                        ${generateTimeSlots(selectedTime)}
                    </div>
                </div>
            </div>
            <div class="flex justify-end space-x-2 mt-4">
                <button type="button" class="cancel-btn px-3 py-1 text-sm border border-gray-300 rounded hover:bg-gray-50">Cancel</button>
                <button type="button" class="confirm-btn px-3 py-1 text-sm bg-blue-600 text-white rounded hover:bg-blue-700">Confirm</button>
            </div>
        </div>
    `);
    
    $input.parent().append($popup);
    
    // Event handlers
    $popup.find('.time-slot').on('click', function() {
        $popup.find('.time-slot').removeClass('selected');
        $(this).addClass('selected');
    });
    
    $popup.find('.cancel-btn').on('click', function() {
        $popup.remove();
    });
    
    $popup.find('.confirm-btn').on('click', function() {
        const dateStr = $popup.find('.date-input').val();
        const timeStr = $popup.find('.time-slot.selected').data('time') || selectedTime;
        $input.val(`${dateStr} ${timeStr}`);
        $popup.remove();
    });
}

// Initialize when jQuery is ready
$(document).ready(function() {
    console.log('jQuery ready, initializing datetime picker');
    initDateTimePicker();
    testDateTimePicker();
});

// Also initialize when window loads (fallback)
window.addEventListener('load', function() {
    if (typeof $ !== 'undefined') {
        console.log('Window loaded, initializing datetime picker');
        setTimeout(function() {
            initDateTimePicker();
            testDateTimePicker();
        }, 100);
    }
});

function generateTimeSlots(selectedTime) {
    let slots = '';
    for (let hour = 9; hour < 17; hour++) {
        for (let minute = 0; minute < 60; minute += 30) {
            const timeStr = `${hour.toString().padStart(2, '0')}:${minute.toString().padStart(2, '0')}`;
            const displayTime = formatTime(timeStr);
            const isSelected = timeStr === selectedTime ? 'selected' : '';
            slots += `<div class="time-slot ${isSelected}" data-time="${timeStr}">${displayTime}</div>`;
        }
    }
    return slots;
}

function formatTime(timeString) {
    const [hour, minute] = timeString.split(':');
    const hourInt = parseInt(hour);
    const ampm = hourInt >= 12 ? 'PM' : 'AM';
    const displayHour = hourInt > 12 ? hourInt - 12 : (hourInt === 0 ? 12 : hourInt);
    return `${displayHour}:${minute} ${ampm}`;
}