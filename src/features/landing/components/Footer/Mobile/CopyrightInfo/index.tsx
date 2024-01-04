import { Stack } from '@mui/material';
import React from 'react';
import { useTranslation } from 'react-i18next';

import UITypography from '@/components/ui/UITypography/UITypography';

function CopyrightInfo() {
  const { t } = useTranslation();
  return (
    <Stack alignItems="center" sx={{ color: '#404142', pt: '16px' }}>
      <UITypography variant="medium15">{t('footer.copyright')}</UITypography>
    </Stack>
  );
}

export default CopyrightInfo;
