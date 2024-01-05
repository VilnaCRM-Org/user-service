import { Stack } from '@mui/material';
import React from 'react';
import { useTranslation } from 'react-i18next';

import { UiButton } from '../../../../../components/ui';

function AuthenticationButtons() {
  const { t } = useTranslation();
  return (
    <Stack spacing={1} direction="row">
      <UiButton variant="outlined" size="small">
        {t('header.actions.log_in')}
      </UiButton>
      <UiButton variant="contained" size="small">
        {t('header.actions.try_it_out')}
      </UiButton>
    </Stack>
  );
}

export default AuthenticationButtons;
