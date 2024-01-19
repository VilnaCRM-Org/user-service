import { Box } from '@mui/material';
import React from 'react';
import { Trans, useTranslation } from 'react-i18next';

import { UiButton, UiTypography } from '@/components/ui';

import { mainTitleStyles } from './styles';

function MainTitle() {
  const { t } = useTranslation();
  return (
    <Box>
      <UiTypography variant="h2" sx={mainTitleStyles.title}>
        {t('for_who.heading_main')}
      </UiTypography>
      <UiTypography
        sx={mainTitleStyles.description}
        variant="bodyText18"
        maxWidth="343px"
      >
        <Trans i18nKey="for_who.text_main" />
      </UiTypography>
      <UiButton variant="contained" size="medium" sx={mainTitleStyles.button}>
        {t('for_who.button_text')}
      </UiButton>
    </Box>
  );
}

export default MainTitle;
