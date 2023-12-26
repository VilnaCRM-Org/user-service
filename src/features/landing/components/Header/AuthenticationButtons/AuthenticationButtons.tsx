'use client';

import { Stack } from '@mui/material';
import React from 'react';
import { useTranslation } from 'react-i18next';

import CustomButton from '@/components/ui/UIButton/UIButton';

function Buttons() {
  const { t } = useTranslation();
  return (
    <Stack spacing={1} direction="row">
      <CustomButton variant="outlined" size="small" fullWidth>
        {t('header.actions.log_in')}
      </CustomButton>
      <CustomButton variant="contained" size="small" fullWidth>
        {t('header.actions.try_it_out')}
      </CustomButton>
    </Stack>
  );
}

export default Buttons;
