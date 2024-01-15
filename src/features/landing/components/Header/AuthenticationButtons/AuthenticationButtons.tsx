/* eslint-disable react/jsx-no-bind */
import { Stack } from '@mui/material';
import React from 'react';
import { useTranslation } from 'react-i18next';

import { UiButton } from '../../../../../components/ui';
import { scrollTo } from '../../../utils/helpers/scrollTo';

function AuthenticationButtons() {
  function handleOnCLickScroll() {
    scrollTo('signUp');
  }

  const { t } = useTranslation();
  return (
    <Stack spacing={1} direction="row">
      <UiButton variant="outlined" size="small" onClick={handleOnCLickScroll}>
        {t('header.actions.log_in')}
      </UiButton>
      <UiButton variant="contained" size="small" onClick={handleOnCLickScroll}>
        {t('header.actions.try_it_out')}
      </UiButton>
    </Stack>
  );
}

export default AuthenticationButtons;
