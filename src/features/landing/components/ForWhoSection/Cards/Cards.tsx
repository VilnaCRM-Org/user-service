import { Box, Stack } from '@mui/material';
import React from 'react';
import { Trans, useTranslation } from 'react-i18next';

import { SmallContainedBtn } from '@/components/UiButton';
import { DefaultTypography } from '@/components/UiTypography';

import Vector from '../../../assets/svg/for-who/yellowVector.svg';

import styles from './styles';

function Cards(): React.ReactElement {
  const { t } = useTranslation();
  return (
    <Stack flexDirection="column" sx={styles.wrapper}>
      <DefaultTypography sx={styles.secondTitle}>
        {t('for_who.heading_secondary')}
      </DefaultTypography>
      <Stack sx={styles.cardWrapper}>
        <Stack sx={styles.cardItem}>
          <Box
            component="img"
            loading="lazy"
            decoding="async"
            src={Vector.src}
            alt={t('for_who.vector_alt')}
            sx={styles.img}
          />
          <DefaultTypography variant="bodyText18" sx={styles.optionText}>
            <Trans i18nKey="for_who.card_text_title" />
          </DefaultTypography>
        </Stack>
        <Stack sx={styles.cardItem}>
          <Box
            component="img"
            loading="lazy"
            decoding="async"
            src={Vector.src}
            alt={t('for_who.vector_alt')}
            sx={styles.img}
          />
          <DefaultTypography variant="bodyText18" sx={styles.optionText}>
            {t('for_who.card_text_business')}
          </DefaultTypography>
        </Stack>
      </Stack>
      <SmallContainedBtn sx={styles.button} href="#signUp">
        {t('for_who.button_text')}
      </SmallContainedBtn>
    </Stack>
  );
}

export default Cards;
