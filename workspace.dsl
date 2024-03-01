workspace {

    !identifiers hierarchical

    model {
        properties {
            "structurizr.groupSeparator" "/"
        }

        softwareSystem = softwareSystem "Software System" {
            userService = container "Software System" {

                group "Application" {
                    registerUserProcessor = component "RegisterUserProcessor" {
                        tags "Item"
                    }
                    confirmUserProcessor = component "ConfirmUserProcessor" {
                        tags "Item"
                    }
                    userPatchProcessor = component "UserPatchProcessor" {
                        tags "Item"
                    }
                    userPutProcessor = component "UserPutProcessor" {
                        tags "Item"
                    }
                    updateUserResolver = component "UpdateUserResolver" {
                        tags "Item"
                    }
                    registerUserResolver = component "RegisterUserResolver" {
                        tags "Item"
                    }
                    confirmUserResolver = component "ConfirmUserResolver" {
                        tags "Item"
                    }
                    updateUserCommandHandler = component "UpdateUserCommandHandler" {
                        tags "Item"
                    }
                    confirmUserCommandHandler = component "ConfirmUserCommandHandler" {
                        tags "Item"
                    }
                    registerUserCommandHandler = component "RegisterUserCommandHandler" {
                        tags "Item"
                    }
                    sendConfirmationEmailCommandHandler = component "SendConfirmationEmailCommandHandler" {
                        tags "Item"
                    }
                    userRegisteredEventSubscriber = component "UserRegisteredEventSubsctiber" {
                        tags "Item"
                    }
                    userConfirmedEventSubscriber = component "UserConfirmedEventSubsctiber" {
                        tags "Item"
                    }
                    confirmationEmailSentEventSubscriber = component "ConfirmationEmailSentEventSubscriber" {
                        tags "Item"
                    }
                }

                group "Domain" {
                    user = component "User" "" "Entity" {
                        tags "Item"
                    }
                    token = component "ConfirmationToken" "" "Entity" {
                        tags "Item"
                    }
                }

                group "Infrastructure" {
                    userRepository = component "MariaDBUserRepository" {
                        tags "Item"
                    }
                    tokenRepository = component "RedisTokenRepository" {
                        tags "Item"
                    }
                }

                database = component "Database" "Stores user, information, hashed authentication credentials, access rights, oauth credentials, etc." "MariaDB" {
                    tags "Database"
                }
                cache = component "Cache" "Stores confirmation token, doctrine query cache" "Elasticache" {
                    tags "Database"
                }

                registerUserProcessor -> registerUserCommandHandler
                registerUserResolver -> registerUserCommandHandler
                confirmUserProcessor -> confirmUserCommandHandler
                confirmUserResolver -> confirmUserCommandHandler
                confirmUserCommandHandler -> userConfirmedEventSubscriber
                registerUserCommandHandler -> userRegisteredEventSubscriber
                userRegisteredEventSubscriber -> sendConfirmationEmailCommandHandler
                sendConfirmationEmailCommandHandler -> confirmationEmailSentEventSubscriber
                userPatchProcessor -> updateUserCommandHandler
                userPutProcessor -> updateUserCommandHandler
                updateUserResolver -> updateUserCommandHandler
                confirmationEmailSentEventSubscriber -> token "Create"
                userConfirmedEventSubscriber -> token "Delete"
                userRepository -> user
                tokenRepository -> token
                userRepository -> database "Save and load"
                tokenRepository -> cache "Save and load"
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
        }
    }
}