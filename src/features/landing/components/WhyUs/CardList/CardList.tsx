import { Grid, useMediaQuery, useTheme } from '@mui/material';
import React from 'react';
import { Pagination } from 'swiper/modules';
import { Swiper, SwiperSlide } from 'swiper/react';

import { CardItem } from '@/components/CardItem';

import { WHY_WE_CARD_ITEMS } from '../../../utils/constants/constants';

import 'swiper/css';
import 'swiper/css/pagination';
import { cardListStyles } from './styles';

function CardList() {
  const theme = useTheme();
  const mobile = useMediaQuery(theme.breakpoints.down('sm'));

  return (
    // eslint-disable-next-line react/jsx-no-useless-fragment
    <>
      {!mobile ? (
        <Grid sx={cardListStyles.grid}>
          {WHY_WE_CARD_ITEMS.map(item => (
            <CardItem item={item} type="WhyUs" key={item.id} />
          ))}
        </Grid>
      ) : (
        <Swiper pagination modules={[Pagination]} className="mySwiper">
          <Grid sx={cardListStyles.grid}>
            {WHY_WE_CARD_ITEMS.map(item => (
              <SwiperSlide key={item.id}>
                <CardItem item={item} type="WhyUs" />
              </SwiperSlide>
            ))}
          </Grid>
        </Swiper>
      )}
    </>
  );
}

export default CardList;
