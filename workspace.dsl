workspace {

    !identifiers hierarchical

    model {
        properties {
            "structurizr.groupSeparator" "/"
        }

        softwareSystem = softwareSystem "VilnaCRM" {
            userService = container "User Service" {

                group "Application" {
                    registerUserProcessor = component "RegisterUserProcessor" "Processes HTTP requests for user registration" "RequestProcessor" {
                        tags "Item"
                    }
                    confirmUserProcessor = component "ConfirmUserProcessor" "Processes HTTP requests for user confirmation" "RequestProcessor" {
                        tags "Item"
                    }
                    userPatchProcessor = component "UserPatchProcessor" "Processes HTTP requests for updating user" "RequestProcessor" {
                        tags "Item"
                    }
                    userPutProcessor = component "UserPutProcessor" "Processes HTTP requests for replacing user" "RequestProcessor" {
                        tags "Item"
                    }
                    updateUserResolver = component "UpdateUserResolver" "Processes GraphQL requests for updating user" "MutationResolver" {
                        tags "Item"
                    }
                    registerUserResolver = component "RegisterUserResolver" "Processes GraphQL requests for user registration" "MutationResolver" {
                        tags "Item"
                    }
                    confirmUserResolver = component "ConfirmUserResolver" "Processes GraphQL requests for user confirmation" "MutationResolver" {
                        tags "Item"
                    }
                    updateUserCommandHandler = component "UpdateUserCommandHandler" "Handles UpdateUserCommand" "CommandHandler" {
                        tags "Item"
                    }
                    confirmUserCommandHandler = component "ConfirmUserCommandHandler" "Handles ConfirmUserCommand" "CommandHandler" {
                        tags "Item"
                    }
                    registerUserCommandHandler = component "RegisterUserCommandHandler" "Handles RegisterUserCommand" "CommandHandler" {
                        tags "Item"
                    }
                    sendConfirmationEmailCommandHandler = component "SendConfirmationEmailCommandHandler" "Handles " "CommandHandler" {
                        tags "Item"
                    }
                    userRegisteredEventSubscriber = component "UserRegisteredEventSubscriber" "Handles UserRegisteredEvent" "EventSubscriber" {
                        tags "Item"
                    }
                    userConfirmedEventSubscriber = component "UserConfirmedEventSubscriber" "Handles UserConfirmedEvent" "EventSubscriber" {
                        tags "Item"
                    }
                    confirmationEmailSentEventSubscriber = component "ConfirmationEmailSentEventSubscriber" "Handles ConfirmationEmailSentEvent" "EventSubscriber" {
                        tags "Item"

                    }
                    emailChangedEventSubscriber = component "EmailChangedEventSubscriber" "Handles EmailChangedEvent" "EventSubscriber" {
                        tags "Item"
                    }
                    passwordChangedEventSubscriber = component "PasswordChangedEventSubscriber" "Handles PasswordChangedEvent" "EventSubscriber" {
                        tags "Item"
                    }
                }

                group "Domain" {
                    user = component "User" "Represents user" "Entity" {
                        tags "Item"
                    }
                    token = component "ConfirmationToken" "Represents confirmation token" "Entity" {
                        tags "Item"
                    }
                }

                group "Infrastructure" {
                    userRepository = component "MariaDBUserRepository" "Manages access to users" "Repository" {
                        tags "Item"
                    }
                    tokenRepository = component "RedisTokenRepository" "Manages access to confirmation tokens" "Repository" {
                        tags "Item"
                    }
                    mailer = component "Symfony Mailer" "Manages sending of emails" {
                        tags "Item"
                    }
                    messenger = component "Symfony Messenger" "Manages background tasks" {
                        tags "Item"
                    }
                }

                database = component "Database" "Stores user, information, hashed authentication credentials, access rights, oauth credentials, etc." "MariaDB" {
                    tags "Database"
                }
                cache = component "Cache" "Stores confirmation token, doctrine query cache" "Elasticache" {
                    tags "Cache"
                }
                sqs = component "AWS SQS" "Message broker for sending emails" "AWS SQS" {
                    tags "MessageBroker"
                }

                registerUserProcessor -> registerUserCommandHandler "dispatches RegisterUserCommand"
                registerUserResolver -> registerUserCommandHandler "dispatches RegisterUserCommand"
                confirmUserProcessor -> confirmUserCommandHandler "dispatches ConfirmUserCommand"
                confirmUserResolver -> confirmUserCommandHandler "dispatches ConfirmUserCommand"
                confirmUserCommandHandler -> userConfirmedEventSubscriber "publishes UserConfirmedEvent"
                registerUserCommandHandler -> userRegisteredEventSubscriber "publishes UserRegisteredEvent"
                registerUserCommandHandler -> user "creates"
                updateUserCommandHandler -> emailChangedEventSubscriber "publishes EmailChangedEvent"
                updateUserCommandHandler -> passwordChangedEventSubscriber "publishes PasswordChangedEvent"
                emailChangedEventSubscriber -> sendConfirmationEmailCommandHandler "dispatches SendConfirmationEmailCommand"
                userRegisteredEventSubscriber -> sendConfirmationEmailCommandHandler "dispatches SendConfirmationEmailCommand"
                sendConfirmationEmailCommandHandler -> confirmationEmailSentEventSubscriber "publishes ConfirmationEmailSentEvent"
                passwordChangedEventSubscriber -> messenger "adds email to queue"
                confirmationEmailSentEventSubscriber -> messenger "adds email to queue"
                mailer -> messenger "consumes emails"
                userPatchProcessor -> updateUserCommandHandler "dispatches UpdateUserCommand"
                userPutProcessor -> updateUserCommandHandler "dispatches UpdateUserCommand"
                updateUserResolver -> updateUserCommandHandler "dispatches UpdateUserCommand"
                confirmationEmailSentEventSubscriber -> token "creates"
                userConfirmedEventSubscriber -> token "deletes"
                userRepository -> user "save and load"
                tokenRepository -> token "save and load"
                userRepository -> database "accesses data"
                tokenRepository -> cache "accesses data"
                mailer -> sqs "publish message"
            }
        }
    }

    views {
        component softwareSystem.userService "Components_All" {
            include *
        }

        styles {
            element "Item" {
                color white
                background #34abeb
            }
            element "Database" {
                color white
                shape cylinder
                background #34abeb
            }
            element "Cache" {
                color white
                shape cylinder
                background #34abeb
            }
            element "MessageBroker" {
                color white
                shape pipe
                background #34abeb
            }
        }
    }
}