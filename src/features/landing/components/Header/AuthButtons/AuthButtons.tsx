import { Stack } from '@mui/material';
import React from 'react';
import { useTranslation } from 'react-i18next';

import { SmallContainedBtn, SmallOutlinedBtn } from '@/components/UiButton';

import styles from './styles';

function AuthButtons(): React.ReactElement {
  const { t } = useTranslation();
  return (
    <Stack spacing={1} direction="row" sx={styles.wrapper}>
      <SmallOutlinedBtn href="#signUp">
        {t('header.actions.log_in')}
      </SmallOutlinedBtn>
      <SmallContainedBtn href="#signUp">
        {t('header.actions.try_it_out')}
      </SmallContainedBtn>
    </Stack>
  );
}

export default AuthButtons;
