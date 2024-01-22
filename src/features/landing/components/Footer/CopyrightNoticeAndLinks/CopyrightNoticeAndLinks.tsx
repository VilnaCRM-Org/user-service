import { Box, Stack } from '@mui/material';
import React from 'react';
import { useTranslation } from 'react-i18next';

import { UiTypography } from '@/components';

import { ISocialMedia } from '../../../types/social-media';
import { SocialMediaList } from '../SocialMediaList';

import { copyrightNoticeAndLinksStyles } from './styles';

function CopyrightNoticeAndLinks({
  socialLinks,
}: {
  socialLinks: ISocialMedia[];
}) {
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
        <SocialMediaList socialLinks={socialLinks} />
      </Stack>
    </>
  );
}

export default CopyrightNoticeAndLinks;
