'use client';

import { Box, Stack, Typography } from '@mui/material';
import React from 'react';
import { useTranslation } from 'react-i18next';

import NavList from '../NavList/NavList';

function CopyrightNoticeAndLinks() {
  const { t } = useTranslation();

  return (
    <>
      <Typography>{t('footer.copyright')}</Typography>
      <Stack direction="row" spacing={1} alignItems="center">
        <Box
          sx={{
            padding: '8px 16px',
            borderRadius: '8px',
            background: '#fff',
            border: '1px solid  #D0D4D8)',
          }}
        >
          <Typography>info@vilnacrm.com</Typography>
        </Box>
        <NavList />
      </Stack>
    </>
  );
}

export default CopyrightNoticeAndLinks;
