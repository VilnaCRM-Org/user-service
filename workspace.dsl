workspace {
    model {
        !identifiers hierarchical

        properties {
            "structurizr.groupSeparator" "/"
        }

        softwareSystem = softwareSystem "Software System" {
            website = container "Layout" {
                header = component "Header" {
                    tags "Item"
                }
                drawer = component "Drawer" {
                    tags "Item"
                }
                navigation = component "NavigationMenu" {
                    tags "Item"
                }
                aboutUs = component "AboutUsSection" {
                    tags "Item"
                }
                aboutUsDescription = component "AboutUsContent" {
                    tags "Item"
                }
                deviceMock = component "DevicePreview" {
                    tags "Item"
                }
                whyUs = component "WhyUsSection" {
                    tags "Item"
                }
                slider = component "MobileSlider" {
                    tags "Item"
                }
                cardList = component "DesktopCardList" {
                    tags "Item"
                }
                whyUsDescription = component "WhyUsContent" {
                    tags "Item"
                }
                possibilities = component "PossibilitiesSection" {
                    tags "Item"
                }
                slider2 = component "MobileSliderSecond" {
                    tags "Item"
                }
                cardList2 = component "DesktopCardListSecond" {
                    tags "Item"
                }
                possibilitiesDescription = component "PossibilitiesContent" {
                    tags "Item"
                }
                authSection = component "AuthenticationSection" {
                    tags "Item"
                }
                registrationForm = component "RegistrationForm" {
                    tags "Item"
                }
                registrationBySocial = component "SocialRegistration" {
                    tags "Item"
                }
                registrationDescription = component "RegistrationContent" {
                    tags "Item"
                }
                footer = component "Footer" {
                    tags "Item"
                }
                privacyPolicy = component "PrivacyPolicyLink" {
                    tags "Item"
                }
                email = component "EmailLink" {
                    tags "Item"
                }
                navigationSosialLinks = component "SocialMediaLinks" {
                    tags "Item"
                }
                Header -> drawer
                Header -> navigation
                aboutUs -> deviceMock
                aboutUs -> aboutUsDescription
                whyUs -> slider
                whyUs -> whyUsDescription
                whyUs -> cardList
                possibilities -> slider2
                possibilities -> cardList2
                possibilities -> possibilitiesDescription
                authSection -> registrationForm
                authSection -> registrationBySocial
                authSection -> registrationDescription
                footer -> privacyPolicy
                footer -> email
                footer -> navigationSosialLinks
            }
        }
    }

    views {
        component softwareSystem.website "Components" {
            include *
        }
        
        styles {
            element "Item" {
                color white
                background "#34abeb"
            }
        }
    }
}