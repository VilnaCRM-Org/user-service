/* eslint-disable react/jsx-no-bind */
import { Stack, Link } from '@mui/material';
import React from 'react';
import { useTranslation } from 'react-i18next';

import { UiButton } from '../../../../../components/ui';

import { authenticationButtonsStyles } from './styles';

function AuthenticationButtons() {
  const { t } = useTranslation();
  return (
    <Stack spacing={1} direction="row" sx={authenticationButtonsStyles.wrapper}>
      <Link href="#signUp">
        <UiButton variant="outlined" size="small">
          {t('header.actions.log_in')}
        </UiButton>
      </Link>
      <Link href="#signUp">
        <UiButton variant="contained" size="small">
          {t('header.actions.try_it_out')}
        </UiButton>
      </Link>
    </Stack>
  );
}

export default AuthenticationButtons;
