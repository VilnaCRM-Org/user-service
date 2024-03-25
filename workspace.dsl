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
                aboutUs = component "AboutUs" {
                    tags "Item"
                }
                   whyUs = component "WhyUs" {
                    tags "Item"
                }
                   possibilities = component "Possibilities" {
                    tags "Item"
                }
                   authSection = component "AuthSection" {
                    tags "Item"
                }
                    footer = component "Footer" {
                    tags "Item"
                }
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