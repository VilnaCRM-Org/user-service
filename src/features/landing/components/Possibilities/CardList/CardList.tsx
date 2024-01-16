import { Grid } from '@mui/material';
import React from 'react';
import { Pagination } from 'swiper/modules';
import { Swiper, SwiperSlide } from 'swiper/react';

import 'swiper/css';
import 'swiper/css/pagination';
import Wix from '@/assets/img/TooltipIcons/1.png';
import WordPress from '@/assets/img/TooltipIcons/2.png';
import Zapier from '@/assets/img/TooltipIcons/3.png';
import Shopify from '@/assets/img/TooltipIcons/4.png';
import Magento from '@/assets/img/TooltipIcons/5.png';
import Joomla from '@/assets/img/TooltipIcons/6.png';
import Drupal from '@/assets/img/TooltipIcons/7.png';
import WooCommerce from '@/assets/img/TooltipIcons/8.png';
import { CardItem } from '@/components/CardItem';

import { UNLIMITED_INTEGRATIONS_CARD_ITEMS } from '../../../utils/constants/constants';

import { cardListStyles } from './styles';

function CardList() {
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
    <>
      <Grid sx={cardListStyles.grid}>
        {UNLIMITED_INTEGRATIONS_CARD_ITEMS.map(item => (
          <CardItem
            key={item.id}
            item={item}
            type="small"
            imageList={imageList}
          />
        ))}
      </Grid>
      <Grid sx={cardListStyles.gridMobile}>
        <Swiper
          pagination
          modules={[Pagination]}
          spaceBetween={20}
          className="swiper-wrapper"
        >
          {UNLIMITED_INTEGRATIONS_CARD_ITEMS.map(item => (
            <SwiperSlide key={item.id}>
              <CardItem item={item} type="small" />
            </SwiperSlide>
          ))}
        </Swiper>
      </Grid>
    </>
  );
}

export default CardList;
