import { Grid } from '@mui/material';
import React from 'react';
import { Pagination } from 'swiper/modules';
import { Swiper, SwiperSlide } from 'swiper/react';

import { CardItem } from '@/components/CardItem';

import { UNLIMITED_INTEGRATIONS_CARD_ITEMS } from '../../../utils/constants/constants';

import 'swiper/css';
import 'swiper/css/pagination';
import { cardListStyles } from './styles';

function CardList() {
  return (
    <>
      <Grid sx={cardListStyles.grid}>
        {UNLIMITED_INTEGRATIONS_CARD_ITEMS.map(item => (
          <CardItem key={item.id} item={item} type="small" />
        ))}
      </Grid>
      <Grid sx={cardListStyles.gridMobile}>
        <Swiper pagination modules={[Pagination]} className="swiper-wrapper">
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
