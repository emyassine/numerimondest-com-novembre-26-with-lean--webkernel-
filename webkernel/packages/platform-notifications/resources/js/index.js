import notification from './components/notification.js'
import notifications, {
    setUpNotifications,
} from './components/notifications.js'
import {
    Action as NotificationAction,
    ActionGroup as NotificationActionGroup,
    Notification,
} from './notification.js'
import './broadcast-notifications.js'

window.PlatformNotificationAction = NotificationAction
window.PlatformNotificationActionGroup = NotificationActionGroup
window.PlatformNotification = Notification

document.addEventListener('alpine:init', () => {
    window.Alpine.data('notificationComponent', notification)
    window.Alpine.data('notificationsComponent', notifications)
})

document.addEventListener('livewire:init', setUpNotifications)
