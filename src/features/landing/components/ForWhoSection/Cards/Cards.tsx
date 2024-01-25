import { Box, Stack } from '@mui/material';
import React from 'react';
import { Trans, useTranslation } from 'react-i18next';

import { UiButton, UiTypography } from '@/components';

import Vector from '../../../assets/svg/for-who/yellowVector.svg';

import styles from './styles';

function Cards() {
  const { t } = useTranslation();
  return (
    <Stack flexDirection="column" sx={styles.wrapper}>
      <UiTypography maxWidth="373px" sx={styles.secondTitle}>
        {t('for_who.heading_secondary')}
      </UiTypography>
      <Stack sx={styles.cardWrapper}>
        <Stack sx={styles.cardItem}>
          <Box
            component="img"
            loading="lazy"
            decoding="async"
            src={Vector.src}
            alt="vector"
            sx={styles.img}
          />
          <UiTypography variant="bodyText18" sx={styles.optionText}>
            <Trans i18nKey="for_who.card_text_title" />
          </UiTypography>
        </Stack>
        <Stack sx={styles.cardItem}>
          <Box
            component="img"
            loading="lazy"
            decoding="async"
            src={Vector.src}
            alt="vector"
            sx={styles.img}
          />
          <UiTypography variant="bodyText18" sx={styles.optionText}>
            {t('for_who.card_text_business')}
          </UiTypography>
        </Stack>
      </Stack>
      <UiButton
        variant="contained"
        size="small"
        sx={styles.button}
        href="#signUp"
      >
        {t('for_who.button_text')}
      </UiButton>
    </Stack>
  );
}

export default Cards;
