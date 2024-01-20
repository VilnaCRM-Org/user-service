import { Grid } from '@mui/material';
import { useTranslation } from 'react-i18next';
import { Pagination } from 'swiper/modules';
import { Swiper, SwiperSlide } from 'swiper/react';

import { UiButton, UiCardItem } from '@/components';

import 'swiper/css';
import 'swiper/css/pagination';

import { LargeCardListProps } from '../../../types/why-we/types';

import { cardListStyles } from './styles';

function CardList({ largeCarditemList }: LargeCardListProps) {
  const { t } = useTranslation();

  return (
    <>
      <Grid sx={cardListStyles.grid}>
        {largeCarditemList.map(item => (
          <UiCardItem item={item} type="large" key={item.id} />
        ))}
      </Grid>
      <Grid sx={cardListStyles.gridMobile}>
        <Swiper
          pagination
          modules={[Pagination]}
          className="swiper-wrapper"
          spaceBetween={12}
          slidesPerView={1.04}
        >
          {largeCarditemList.map(item => (
            <SwiperSlide key={item.id}>
              <UiCardItem item={item} type="large" />
            </SwiperSlide>
          ))}
        </Swiper>
        <UiButton variant="contained" size="small" sx={cardListStyles.button}>
          {t('why_we.buttonText')}
        </UiButton>
      </Grid>
    </>
  );
}

export default CardList;
