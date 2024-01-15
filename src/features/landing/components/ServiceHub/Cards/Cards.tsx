import { Stack } from '@mui/material';
import Image from 'next/image';
import React from 'react';

import { UiButton, UiTypography } from '@/components/ui';

import Vector from '../../../assets/svg/service-hub/yellowVector.svg';

import { cardsStyles } from './styles';
import styles from './styles.module.scss';

function Cards() {
  return (
    <Stack flexDirection="column" sx={cardsStyles.wrapper}>
      <UiTypography maxWidth="373px" sx={cardsStyles.secondTitle}>
        Наша CRM ідеально підійде, якщо ви:
      </UiTypography>
      <Stack gap="16px" sx={cardsStyles.cardWrapper}>
        <Stack sx={cardsStyles.cardItem}>
          <Image
            src={Vector}
            alt="vector"
            width={24}
            height={24}
            className={styles.img}
          />
          <UiTypography variant="bodyText18" sx={cardsStyles.optionText}>
            Приватний підприємець — психолог, репетитор чи дропшипер
          </UiTypography>
        </Stack>
        <Stack sx={cardsStyles.cardItem}>
          <Image
            src={Vector}
            alt="vector"
            width={24}
            height={24}
            className={styles.img}
          />
          <UiTypography variant="bodyText18" sx={cardsStyles.optionText}>
            локальний проект середнього масштабу — онлайн-курси, дизайн-студія
            чи невеликий аутсорс
          </UiTypography>
        </Stack>
      </Stack>
      <UiButton variant="contained" size="small" sx={cardsStyles.button}>
        Спробувати
      </UiButton>
    </Stack>
  );
}

export default Cards;
