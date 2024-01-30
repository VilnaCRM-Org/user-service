/* eslint-disable react/jsx-no-bind */
import { Stack } from '@mui/material';
import React from 'react';
import { useTranslation } from 'react-i18next';

import { UiButton } from '../../../../../components';

import { authButtonsStyles } from './styles';

function AuthButtons(): React.ReactElement {
  const { t } = useTranslation();
  return (
    <Stack spacing={1} direction="row" sx={authButtonsStyles.wrapper}>
      <UiButton variant="outlined" size="small" href="#signUp">
        {t('header.actions.log_in')}
      </UiButton>
      <UiButton variant="contained" size="small" href="#signUp">
        {t('header.actions.try_it_out')}
      </UiButton>
    </Stack>
  );
}

export default AuthButtons;
