import { Stack } from '@mui/material';
import React from 'react';
import { useTranslation } from 'react-i18next';

import { UiTypography } from '@/components/ui';

function CopyrightInfo() {
  const { t } = useTranslation();
  return (
    <Stack alignItems="center" sx={{ color: '#404142', pt: '16px' }}>
      <UiTypography variant="medium15">{t('footer.copyright')}</UiTypography>
    </Stack>
  );
}

export default CopyrightInfo;
