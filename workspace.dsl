workspace {

    !identifiers hierarchical

    model {
        user = person "Customer"
        website = softwareSystem "Website" "Public website for users with SSR"
        crm = softwareSystem "CRM" "Protected website for customers"
        bff = softwareSystem "BFF" "2FA, Cookies, OAuth client for user service and backend for frontend"
        gateway = softwareSystem "API Gateway" "Provides load balanser, logging tracing, request limiting"
        iam = softwareSystem "IAM" "Provides auth cache and authorization management, security and access logs"
        userService = softwareSystem "User Service" "Allows to store users (their profiles and settings), authenticate, register, OAuth"
        paymentService = softwareSystem "Payment Service" "Allows to pay for plan and create invoices for user customers"
        coreService = softwareSystem "Core Service (CRM, Project)" " Stores all products, contacts, employees, orders, integrations settings, IP telephony integration settings, invite users to projects, dashboards, kanban boards, users tasks"
        analyticsService = softwareSystem "Analytics Service" "Stores all analytics data, widgets data"
        notificationService = softwareSystem "Notification Service" "Handle all microservices notifications and stores notifications. Sends notifications for mobile, browser through emails and SSE"
        webhookService = softwareSystem "Webhook Service" "Handle all microservices webhooks and stores webhooks settings"

        user -> website
        user -> crm
        crm -> bff
        website -> bff
        bff -> gateway
        crm -> gateway
        gateway -> iam
        iam -> userService
        gateway -> userService
        gateway -> paymentService
        gateway -> coreService
        gateway -> analyticsService
        gateway -> notificationService
        gateway -> webhookService
    }

    views {
        styles {
            element "Person" {
                shape Person
                color white
                background #070cab
            }
            element "Software System" {
                color white
                background #070cab
            }
            element "Database" {
                shape cylinder
            }
        }

    }

}