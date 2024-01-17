import { Grid } from '@mui/material';
import React from 'react';
import { Pagination } from 'swiper/modules';
import { Swiper, SwiperSlide } from 'swiper/react';

import { CardItem } from '@/components/CardItem';

import { WHY_WE_CARD_ITEMS } from '../../../utils/constants/constants';

import 'swiper/css';
import 'swiper/css/pagination';
import { cardListStyles } from './styles';

// eslint-disable-next-line import/order
import { UiButton } from '@/components/ui';

const pagination = {
  renderBullet(index: number, className: string) {
    return `
    <span class="${className}" style="margin-right: 17px;"></span>`;
  },
}; // i don`t know, should we use that or better without that
function CardList() {
  return (
    <>
      <Grid sx={cardListStyles.grid}>
        {WHY_WE_CARD_ITEMS.map(item => (
          <CardItem item={item} type="large" key={item.id} />
        ))}
      </Grid>
      <Grid sx={cardListStyles.gridMobile}>
        <Swiper
          slidesPerView={1.04}
          pagination={pagination}
          modules={[Pagination]}
          spaceBetween={12}
          className="swiper-wrapper"
        >
          {WHY_WE_CARD_ITEMS.map(item => (
            <SwiperSlide key={item.id}>
              <CardItem item={item} type="large" />
            </SwiperSlide>
          ))}
        </Swiper>
        <UiButton variant="contained" size="small" sx={cardListStyles.button}>
          Спробувати
        </UiButton>
      </Grid>
    </>
  );
}
export default CardList;
