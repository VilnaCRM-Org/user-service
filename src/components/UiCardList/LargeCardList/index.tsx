import { Grid } from '@mui/material';
import { useTranslation } from 'react-i18next';
import { Pagination } from 'swiper/modules';
import { Swiper, SwiperSlide } from 'swiper/react';

import { UiButton } from '@/components';

import 'swiper/css';
import 'swiper/css/pagination';

import UiCardItem from '../../UiCardItem';
import { CardList } from '../types';

import styles from './styles';

function CardList({ cardList }: CardList): React.ReactElement {
  const { t } = useTranslation();

  return (
    <>
      <Grid sx={styles.grid}>
        {cardList.map(item => (
          <UiCardItem item={item} type="large" key={item.id} />
        ))}
      </Grid>
      <Grid sx={styles.gridMobile}>
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
          sx={styles.button}
          href="#signUp"
        >
          {t('why_us.button_text')}
        </UiButton>
      </Grid>
    </>
  );
}

export default CardList;
