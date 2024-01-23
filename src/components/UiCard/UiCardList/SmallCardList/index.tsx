import { Grid } from '@mui/material';
import React from 'react';
import { Pagination } from 'swiper/modules';
import { Swiper, SwiperSlide } from 'swiper/react';

import 'swiper/css';
import 'swiper/css/pagination';

import UiCardItem from '../../UiCardItem';
import { ICardList } from '../types';

import { styles } from './styles';

function CardList({ imageList, cardList }: ICardList) {
  return (
    <>
      <Grid sx={styles.grid}>
        {cardList.map(item => (
          <UiCardItem
            key={item.id}
            item={item}
            type="small"
            imageList={imageList}
          />
        ))}
      </Grid>
      <Grid sx={styles.gridMobile}>
        <Swiper
          pagination
          modules={[Pagination]}
          spaceBetween={12}
          slidesPerView={1.04}
          className="swiper-wrapper"
        >
          {cardList.map(item => (
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