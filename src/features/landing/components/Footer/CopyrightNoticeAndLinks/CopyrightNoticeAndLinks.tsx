import { Box, Stack } from '@mui/material';
import React from 'react';
import { useTranslation } from 'react-i18next';

import { UiTypography } from '@/components/ui';

import { NavList } from '../NavList';

function CopyrightNoticeAndLinks() {
  const { t } = useTranslation();

  return (
    <>
      <UiTypography variant="medium15" sx={{ color: '#404142' }}>
        {t('footer.copyright')}
      </UiTypography>
      <Stack direction="row" gap="14px" alignItems="center">
        <Box
          sx={{
            padding: '8px 16px',
            borderRadius: '8px',
            background: '#fff',
            border: '1px solid  #D0D4D8',
          }}
        >
          <UiTypography variant="medium15" sx={{ color: '#1B2327' }}>
            info@vilnacrm.com
          </UiTypography>
        </Box>
        <NavList />
      </Stack>
    </>
  );
}

export default CopyrightNoticeAndLinks;
