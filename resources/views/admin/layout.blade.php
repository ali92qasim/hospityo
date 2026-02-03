<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Hospityo')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'medical-blue': '#0066CC',
                        'medical-green': '#00A86B',
                        'medical-light': '#F0F8FF',
                        'medical-gray': '#6B7280'
                    }
                }
            }
        }
    </script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
    <script>
        // Initialize Pusher
        const pusher = new Pusher('{{ env("PUSHER_APP_KEY") }}', {
            cluster: '{{ env("PUSHER_APP_CLUSTER") }}',
            forceTLS: true,
            authEndpoint: '/broadcasting/auth',
            auth: {
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            }
        });

        // Debug Pusher connection
        pusher.connection.bind('connected', function() {
            console.log('Pusher connected successfully');
        });
        
        pusher.connection.bind('error', function(err) {
            console.error('Pusher connection error:', err);
        });

        // Subscribe to user's private channel
        const channel = pusher.subscribe('private-user.{{ auth()->id() }}');
        
        // Debug channel subscription
        channel.bind('pusher:subscription_succeeded', function() {
            console.log('Successfully subscribed to private channel');
        });
        
        channel.bind('pusher:subscription_error', function(err) {
            console.error('Channel subscription error:', err);
        });
        
        // Listen for patient assignment events
        channel.bind('PatientAssigned', function(data) {
            console.log('Received notification:', data);
            showLiveNotification(data);
            // Immediately increment count
            const count = document.getElementById('notification-count');
            const currentCount = parseInt(count.textContent) || 0;
            count.textContent = currentCount + 1;
            count.classList.remove('hidden');
        });

        function updateNotificationCount() {
            fetch('/notifications/unread')
                .then(response => response.json())
                .then(data => {
                    const count = document.getElementById('notification-count');
                    if (data.count > 0) {
                        count.textContent = data.count;
                        count.classList.remove('hidden');
                    } else {
                        count.classList.add('hidden');
                    }
                });
        }

        function showLiveNotification(data) {
            // Create toast notification
            const toast = document.createElement('div');
            toast.className = 'fixed top-4 right-4 bg-medical-blue text-white p-4 rounded-lg shadow-lg z-50 cursor-pointer';
            toast.innerHTML = `
                <div class="font-medium">${data.title}</div>
                <div class="text-sm mt-1">${data.message}</div>
                <div class="text-xs mt-2 opacity-75">Click to view patient</div>
            `;
            
            toast.onclick = () => {
                window.location.href = `/visits/${data.visit_id}/workflow`;
            };
            
            document.body.appendChild(toast);
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                toast.remove();
            }, 5000);
        }

        function toggleNotifications() {
            const panel = document.getElementById('notification-panel');
            const isVisible = !panel.classList.contains('opacity-0');
            
            if (isVisible) {
                panel.classList.add('opacity-0', 'invisible');
            } else {
                panel.classList.remove('opacity-0', 'invisible');
                loadNotifications();
            }
        }

        function loadNotifications() {
            fetch('/notifications/unread')
                .then(response => response.json())
                .then(data => {
                    const list = document.getElementById('notification-list');
                    const count = document.getElementById('notification-count');
                    
                    if (data.notifications.length === 0) {
                        list.innerHTML = '<div class="p-4 text-center text-gray-500">No notifications</div>';
                        count.classList.add('hidden');
                    } else {
                        count.textContent = data.count;
                        count.classList.remove('hidden');
                        
                        list.innerHTML = data.notifications.map(notification => `
                            <div class="p-3 border-b border-gray-100 hover:bg-gray-50 cursor-pointer" onclick="handleNotificationClick(${notification.id}, '${notification.data?.visit_id || ''}')">
                                <div class="font-medium text-sm text-gray-800">${notification.title}</div>
                                <div class="text-xs text-gray-600 mt-1">${notification.message}</div>
                                <div class="text-xs text-gray-400 mt-1">${new Date(notification.created_at).toLocaleString()}</div>
                            </div>
                        `).join('');
                    }
                });
        }

        function handleNotificationClick(notificationId, visitId) {
            fetch(`/notifications/${notificationId}/read`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json'
                }
            }).then(() => {
                updateNotificationCount();
                if (visitId) {
                    window.location.href = `/visits/${visitId}/workflow`;
                }
            });
        }

        function markAllAsRead() {
            fetch('/notifications/mark-all-read', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            }).then(() => {
                loadNotifications();
                updateNotificationCount();
            });
        }

        // Load notifications on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateNotificationCount();
        });

        // Close notification panel when clicking outside
        document.addEventListener('click', function(event) {
            const dropdown = document.getElementById('notification-dropdown');
            const panel = document.getElementById('notification-panel');
            
            if (!dropdown.contains(event.target)) {
                panel.classList.add('opacity-0', 'invisible');
            }
        });
    </script>
</head>
<body class="bg-gray-50">
    @include('partials.sidebar')
    
    <div class="ml-64">
        @include('partials.header')
        
        <main class="p-6">
            @include('partials.alerts')
            @yield('content')
        </main>
    </div>
</body>
</html>