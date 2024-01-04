import { Stack } from '@mui/material';
import React from 'react';
import { useTranslation } from 'react-i18next';

import UIButton from '@/components/ui/UIButton/UIButton';
import UITypography from '@/components/ui/UITypography/UITypography';

function TextInfo() {
  const { t } = useTranslation();
  return (
    <Stack maxWidth="43.813rem" mb="50px">
      <UITypography variant="h1" sx={{ textAlign: 'center' }}>
        {t('about_vilna.heading_main')}
      </UITypography>
      <UITypography
        variant="bodyText18"
        sx={{
          mt: '1rem',
          textAlign: 'center',
          mb: '39px',
        }}
      >
        {t('about_vilna.text_main')}
      </UITypography>
      <UIButton variant="contained" size="medium">
        {t('about_vilna.button_main')}
      </UIButton>
    </Stack>
  );
}

export default TextInfo;
