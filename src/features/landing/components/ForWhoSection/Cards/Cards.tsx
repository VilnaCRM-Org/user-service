import { Box, Stack } from '@mui/material';
import React from 'react';
import { Trans, useTranslation } from 'react-i18next';

import { UiButton, UiTypography } from '@/components/ui';

import Vector from '../../../assets/svg/service-hub/yellowVector.svg';

import { cardsStyles } from './styles';

function Cards() {
  const { t } = useTranslation();
  return (
    <Stack flexDirection="column" sx={cardsStyles.wrapper}>
      <UiTypography maxWidth="373px" sx={cardsStyles.secondTitle}>
        {t('for_who.heading_secondary')}
      </UiTypography>
      <Stack sx={cardsStyles.cardWrapper}>
        <Stack sx={cardsStyles.cardItem}>
          <Box
            component="img"
            loading="lazy"
            decoding="async"
            src={Vector.src}
            alt="vector"
            sx={cardsStyles.img}
          />
          <UiTypography variant="bodyText18" sx={cardsStyles.optionText}>
            <Trans i18nKey="for_who.card_text_title" />
          </UiTypography>
        </Stack>
        <Stack sx={cardsStyles.cardItem}>
          <Box
            component="img"
            loading="lazy"
            decoding="async"
            src={Vector.src}
            alt="vector"
            sx={cardsStyles.img}
          />
          <UiTypography variant="bodyText18" sx={cardsStyles.optionText}>
            {t('for_who.card_text_business')}
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
