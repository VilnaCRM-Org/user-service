import { Box } from '@mui/material';
import React from 'react';

import Drupal from '@/assets/img/TooltipIcons/Drupal.png';
import Joomla from '@/assets/img/TooltipIcons/Joomla.png';
import Magento from '@/assets/img/TooltipIcons/Magento.png';
import Shopify from '@/assets/img/TooltipIcons/Shopify.png';
import Wix from '@/assets/img/TooltipIcons/Wix.png';
import WooCommerce from '@/assets/img/TooltipIcons/WooCommerce.png';
import WordPress from '@/assets/img/TooltipIcons/WordPress.png';
import Zapier from '@/assets/img/TooltipIcons/Zapier.png';
import UiCardList from '@/components/UiCard/UiCardList';

import Diamond from '../../assets/svg/why-us/diamond.svg';
import Ruby from '../../assets/svg/why-us/ruby.svg';
import SmallDiamond from '../../assets/svg/why-us/smallDiamond.svg';
import SmallRuby from '../../assets/svg/why-us/smallRuby.svg';

import { RegistrationText } from './RegistrationText';
import { possibilitiesStyles } from './styles';

function Possibilities() {
  const cardList = [
    {
      id: 'item_1',
      imageSrc: Ruby,
      text: 'unlimited_possibilities.cards_texts.text_for_cases',
      title: 'unlimited_possibilities.cards_headings.heading_public_api',
      alt: 'unlimited_possibilities.card_image_titles.title_for_first',
    },
    {
      id: 'item_2',
      imageSrc: SmallDiamond,
      text: 'unlimited_possibilities.cards_texts.text_integrate',
      title: 'unlimited_possibilities.cards_headings.heading_ready_plugins',
      alt: 'unlimited_possibilities.card_image_titles.title_for_second',
    },
    {
      id: 'item_3',
      imageSrc: SmallRuby,
      text: 'unlimited_possibilities.cards_texts.text_get_data',
      title: 'unlimited_possibilities.cards_headings.heading_system',
      alt: 'unlimited_possibilities.card_image_titles.title_for_third',
    },
    {
      id: 'item_4',
      imageSrc: Diamond,
      text: 'unlimited_possibilities.cards_texts.text_for_custom',
      title: 'unlimited_possibilities.cards_headings.heading_libraries',
      alt: 'unlimited_possibilities.card_image_titles.title_for_fourth',
    },
  ];

  const imageList = [
    { image: Wix, alt: 'Wix' },
    { image: WordPress, alt: 'WordPress' },
    { image: Zapier, alt: 'Zapier' },
    { image: Shopify, alt: 'Shopify' },
    { image: Magento, alt: 'Magento' },
    { image: Joomla, alt: 'Joomla' },
    { image: Drupal, alt: 'Drupal' },
    { image: WooCommerce, alt: 'WooCommerce' },
  ];
  return (
    <Box sx={possibilitiesStyles.wrapper} id="Integration" component="section">
      <RegistrationText />
      <UiCardList imageList={imageList} cardList={cardList} type="small" />
    </Box>
  );
}

export default Possibilities;
