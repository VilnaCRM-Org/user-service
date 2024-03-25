import Drupal from '@/assets/svg/TooltipIcons/Drupal.svg';
import Joomla from '@/assets/svg/TooltipIcons/Joomla.svg';
import Magento from '@/assets/svg/TooltipIcons/Magento.svg';
import Shopify from '@/assets/svg/TooltipIcons/Shopify.svg';
import Wix from '@/assets/svg/TooltipIcons/Wix.svg';
import WooCommerce from '@/assets/svg/TooltipIcons/WooCommerce.svg';
import WordPress from '@/assets/svg/TooltipIcons/WordPress.svg';
import Zapier from '@/assets/svg/TooltipIcons/Zapier.svg';

import Diamond from '../../assets/svg/possibilities/diamond.svg';
import Ruby from '../../assets/svg/possibilities/ruby.svg';
import SmallDiamond from '../../assets/svg/possibilities/smallDiamond.svg';
import SmallRuby from '../../assets/svg/possibilities/smallRuby.svg';
import { Card } from '../../types/Card/card-item';
import { ImageList } from '../../types/possibilities/image-list';

export const cardList: Card[] = [
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

export const imageList: ImageList[] = [
  { image: Wix, alt: 'Wix' },
  { image: WordPress, alt: 'WordPress' },
  { image: Zapier, alt: 'Zapier' },
  { image: Shopify, alt: 'Shopify' },
  { image: Magento, alt: 'Magento' },
  { image: Joomla, alt: 'Joomla' },
  { image: Drupal, alt: 'Drupal' },
  { image: WooCommerce, alt: 'WooCommerce' },
];
