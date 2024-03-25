import Diamond from '../../features/landing/assets/svg/possibilities/diamond.svg';
import Ruby from '../../features/landing/assets/svg/possibilities/ruby.svg';
import SmallDiamond from '../../features/landing/assets/svg/possibilities/smallDiamond.svg';
import SmallRuby from '../../features/landing/assets/svg/possibilities/smallRuby.svg';
import WhyUsCodeIcon from '../../features/landing/assets/svg/why-us/code.svg';
import WhyUsIntegrationsIcon from '../../features/landing/assets/svg/why-us/integrations.svg';
import WhyUsMigrationIcon from '../../features/landing/assets/svg/why-us/migration.svg';
import WhyUsServicesIcon from '../../features/landing/assets/svg/why-us/services.svg';
import WhyUsSettingsIcon from '../../features/landing/assets/svg/why-us/settings.svg';
import WhyUsTemplatesIcon from '../../features/landing/assets/svg/why-us/templates.svg';

import { CardItem } from './types';

export const LARGE_CARDLIST_ARRAY: CardItem[] = [
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

export const SMALL_CARDLIST_ARRAY: CardItem[] = [
  {
    type: 'smallCard',
    id: 'item_1',
    imageSrc: Ruby,
    text: 'unlimited_possibilities.cards_texts.text_for_cases',
    title: 'unlimited_possibilities.cards_headings.heading_public_api',
    alt: 'unlimited_possibilities.card_image_titles.title_for_first',
  },
  {
    type: 'smallCard',
    id: 'item_2',
    imageSrc: SmallDiamond,
    text: 'unlimited_possibilities.cards_texts.text_integrate',
    title: 'unlimited_possibilities.cards_headings.heading_ready_plugins',
    alt: 'unlimited_possibilities.card_image_titles.title_for_second',
  },
  {
    type: 'smallCard',
    id: 'item_3',
    imageSrc: SmallRuby,
    text: 'unlimited_possibilities.cards_texts.text_get_data',
    title: 'unlimited_possibilities.cards_headings.heading_system',
    alt: 'unlimited_possibilities.card_image_titles.title_for_third',
  },
  {
    type: 'smallCard',
    id: 'item_4',
    imageSrc: Diamond,
    text: 'unlimited_possibilities.cards_texts.text_for_custom',
    title: 'unlimited_possibilities.cards_headings.heading_libraries',
    alt: 'unlimited_possibilities.card_image_titles.title_for_fourth',
  },
];
