import { Grid } from '@mui/material';
import { useTranslation } from 'react-i18next';
import { Pagination } from 'swiper/modules';
import { Swiper, SwiperSlide } from 'swiper/react';

import { UiButton } from '@/components';

import 'swiper/css';
import 'swiper/css/pagination';

import UiCardItem from '../UiCardItem';

import { largeCardListStyles } from './styles';
import { ICardList } from './types';

function CardList({ cardList }: ICardList) {
  const { t } = useTranslation();

  return (
    <>
      <Grid sx={largeCardListStyles.grid}>
        {cardList.map(item => (
          <UiCardItem item={item} type="large" key={item.id} />
        ))}
      </Grid>
      <Grid sx={largeCardListStyles.gridMobile}>
        <Swiper
          pagination
          modules={[Pagination]}
          className="swiper-wrapper"
          spaceBetween={12}
          slidesPerView={1.04}
        >
          {cardList.map(item => (
            <SwiperSlide key={item.id}>
              <UiCardItem item={item} type="large" />
            </SwiperSlide>
          ))}
        </Swiper>
        <UiButton
          variant="contained"
          size="small"
          sx={largeCardListStyles.button}
        >
          {t('why_we.buttonText')}
        </UiButton>
      </Grid>
    </>
  );
}

export default CardList;
