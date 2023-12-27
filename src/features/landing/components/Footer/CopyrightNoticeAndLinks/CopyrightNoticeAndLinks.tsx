'use client';

import { Box, Stack } from '@mui/material';
import React from 'react';
import { useTranslation } from 'react-i18next';

import UITypography from '@/components/ui/UITypography/UITypography';

import NavList from '../NavList/NavList';

function CopyrightNoticeAndLinks() {
  const { t } = useTranslation();

  return (
    <>
      <UITypography variant="medium15" sx={{ color: '#404142' }}>
        {t('footer.copyright')}
      </UITypography>
      <Stack direction="row" spacing={1} alignItems="center">
        <Box
          sx={{
            padding: '8px 16px',
            borderRadius: '8px',
            background: '#fff',
            border: '1px solid  #D0D4D8)',
          }}
        >
          <UITypography variant="medium15" sx={{ color: '#1B2327' }}>
            info@vilnacrm.com
          </UITypography>
        </Box>
        <NavList />
      </Stack>
    </>
  );
}

export default CopyrightNoticeAndLinks;
