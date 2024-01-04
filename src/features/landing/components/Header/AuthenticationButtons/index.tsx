import { Stack } from '@mui/material';
import React from 'react';
import { useTranslation } from 'react-i18next';

import UIButton from '@/components/ui/UIButton/UIButton';

function AuthenticationButtons() {
  const { t } = useTranslation();
  return (
    <Stack spacing={1} direction="row">
      <UIButton variant="outlined" size="small">
        {t('header.actions.log_in')}
      </UIButton>
      <UIButton variant="contained" size="small">
        {t('header.actions.try_it_out')}
      </UIButton>
    </Stack>
  );
}

export default AuthenticationButtons;
