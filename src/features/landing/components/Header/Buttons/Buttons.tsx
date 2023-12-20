'use client';

import { Button, Stack } from '@mui/material';
import React from 'react';
import { useTranslation } from 'react-i18next';

function Buttons() {
  const { t } = useTranslation();
  return (
    <Stack spacing={1} direction="row">
      <Button
        variant="outlined"
        sx={{
          py: '16px',
          px: '24px',
          borderRadius: '57px',
          fontFamily: 'Golos',
          fontSize: '12px',
          fontWeight: '500',
          lineHeight: '18px',
        }}
      >
        {t('header.actions.log_in')}
      </Button>
      <Button
        variant="contained"
        sx={{
          py: '16px',
          px: '24px',
          borderRadius: '57px',
          fontFamily: 'Golos',
          fontSize: '12px',
          fontWeight: '500',
          lineHeight: '18px',
        }}
      >
        {t('header.actions.try_it_out')}
      </Button>
    </Stack>
  );
}

export default Buttons;
