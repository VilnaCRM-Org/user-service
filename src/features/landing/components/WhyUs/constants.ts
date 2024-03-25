import WhyUsCodeIcon from '../../assets/svg/why-us/code.svg';
import WhyUsIntegrationsIcon from '../../assets/svg/why-us/integrations.svg';
import WhyUsMigrationIcon from '../../assets/svg/why-us/migration.svg';
import WhyUsServicesIcon from '../../assets/svg/why-us/services.svg';
import WhyUsSettingsIcon from '../../assets/svg/why-us/settings.svg';
import WhyUsTemplatesIcon from '../../assets/svg/why-us/templates.svg';
import { Card } from '../../types/Card/card-item';

export const cardList: Card[] = [
  {
    type: 'largeCard',
    id: 'card-item-1',
    imageSrc: WhyUsCodeIcon,
    title: 'why_us.headers.header_open_source',
    text: 'why_us.texts.text_open_source',
    alt: 'why_us.alt_image.alt_open_source',
  },
  {
    type: 'largeCard',
    id: 'card-item-2',
    imageSrc: WhyUsSettingsIcon,
    title: 'why_us.headers.header_ease_of_setup',
    text: 'why_us.texts.text_configure_system',
    alt: 'why_us.alt_image.alt_ease_of_setup',
  },

  {
    type: 'largeCard',
    id: 'card-item-3',
    imageSrc: WhyUsTemplatesIcon,
    title: 'why_us.headers.header_ready_templates',
    text: 'why_us.texts.text_you_have_store',
    alt: 'why_us.alt_image.alt_ready_templates',
  },
  {
    type: 'largeCard',
    id: 'card-item-4',
    imageSrc: WhyUsServicesIcon,
    title: 'why_us.headers.header_ideal_for_services',
    text: 'why_us.texts.text_we_know_specific_needs',
    alt: 'why_us.alt_image.alt_ideal_for_services',
  },
  {
    type: 'largeCard',
    id: 'card-item-5',
    imageSrc: WhyUsIntegrationsIcon,
    title: 'why_us.headers.header_all_required_integrations',
    text: 'why_us.texts.text_connect_your_cms',
    alt: 'why_us.alt_image.alt_all_required_integrations',
  },
  {
    type: 'largeCard',
    id: 'card-item-6',
    imageSrc: WhyUsMigrationIcon,
    title: 'why_us.headers.header_bonus',
    text: 'why_us.texts.text_switch_to_vilna',
    alt: 'why_us.alt_image.alt_bonus',
  },
];
