import { Grid, useMediaQuery, useTheme } from '@mui/material';
import React from 'react';
import { Pagination } from 'swiper/modules';
import { Swiper, SwiperSlide } from 'swiper/react';

import CardItem from '../../../../../components/CardItem/CardItem';
import { WHY_WE_CARD_ITEMS } from '../../../utils/constants/constants';

import 'swiper/css';
import 'swiper/css/pagination';

function CardList() {
  const theme = useTheme();
  const mobile = useMediaQuery(theme.breakpoints.down('sm'));
  const styles = {
    grid: {
      display: 'grid',
      gridTemplateColumns: {
        md: 'repeat(2, 1fr)',
        lg: 'repeat(3, minmax(15.625rem, 24.3125rem))',
      },
      gridTemplateRows: {
        md: 'repeat(2, 1fr)',
        lg: 'repeat(2, minmax(342px, auto))',
        xl: 'repeat(2, minmax(342px, auto))',
      },

      marginTop: '2.5rem',
      gap: '0.813rem',
    },
  };

  return (
    // eslint-disable-next-line react/jsx-no-useless-fragment
    <>
      {!mobile ? (
        <Grid sx={{ ...styles.grid }}>
          {WHY_WE_CARD_ITEMS.map(item => (
            <CardItem item={item} type="WhyUs" key={item.id} />
          ))}
        </Grid>
      ) : (
        <Swiper pagination modules={[Pagination]} className="mySwiper">
          <Grid sx={{ ...styles.grid }}>
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
