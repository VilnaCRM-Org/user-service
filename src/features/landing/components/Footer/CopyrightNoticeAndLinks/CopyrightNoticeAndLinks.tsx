import { Box, Stack } from '@mui/material';
import React from 'react';
import { useTranslation } from 'react-i18next';

import { UiTypography } from '@/components/ui';

import { NavList } from '../NavList';

import { copyrightNoticeAndLinksStyles } from './styles';

function CopyrightNoticeAndLinks() {
  const { t } = useTranslation();

  return (
    <>
      <UiTypography
        variant="medium15"
        sx={copyrightNoticeAndLinksStyles.copyright}
      >
        {t('footer.copyright')}
      </UiTypography>
      <Stack direction="row" gap="14px" alignItems="center">
        <Box sx={copyrightNoticeAndLinksStyles.gmailWrapper}>
          <UiTypography
            variant="medium15"
            sx={copyrightNoticeAndLinksStyles.gmailText}
          >
            info@vilnacrm.com
          </UiTypography>
        </Box>
        <NavList />
      </Stack>
    </>
  );
}

export default CopyrightNoticeAndLinks;
