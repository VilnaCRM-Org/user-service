import Ruby from '../../features/landing/assets/svg/possibilities/ruby.svg';
import WhyUsTemplatesIcon from '../../features/landing/assets/svg/why-us/templates.svg';

import { CardItem } from './types';

export const SMALL_CARD_ITEM: CardItem = {
  type: 'smallCard',
  id: 'item_1',
  imageSrc: Ruby,
  text: 'unlimited_possibilities.cards_texts.text_for_cases',
  title: 'unlimited_possibilities.cards_headings.heading_public_api',
  alt: 'unlimited_possibilities.card_image_titles.title_for_first',
};

export const LARGE_CARD_ITEM: CardItem = {
  type: 'largeCard',
  id: 'card-item-3',
  imageSrc: WhyUsTemplatesIcon,
  title: 'why_us.headers.header_ready_templates',
  text: 'why_us.texts.text_you_have_store',
  alt: 'why_us.alt_image.alt_ready_templates',
};
