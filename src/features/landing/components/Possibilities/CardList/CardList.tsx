import { Grid } from '@mui/material';
import React from 'react';
import { Pagination } from 'swiper/modules';
import { Swiper, SwiperSlide } from 'swiper/react';

import { UiCardItem } from '@/components';

import 'swiper/css';
import 'swiper/css/pagination';
import { SmallCardListProps } from '../../../types/possibilities/types';

import { cardListStyles } from './styles';

function CardList({ imageList, smallCardItemList }: SmallCardListProps) {
  return (
    <>
      <Grid sx={cardListStyles.grid}>
        {smallCardItemList.map(item => (
          <UiCardItem
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
          spaceBetween={12}
          slidesPerView={1.04}
          className="swiper-wrapper"
        >
          {smallCardItemList.map(item => (
            <SwiperSlide key={item.id}>
              <UiCardItem item={item} type="small" />
            </SwiperSlide>
          ))}
        </Swiper>
      </Grid>
    </>
  );
}

export default CardList;
