import { Box, Stack } from '@mui/material';
import React from 'react';
import { useTranslation } from 'react-i18next';

import { UiTypography } from '@/components';

import { ISocialMedia } from '../../../types/social-media';
import { SocialMediaList } from '../SocialMediaList';

import styles from './styles';

function CopyrightNoticeAndLinks({
  socialLinks,
}: {
  socialLinks: ISocialMedia[];
}): React.ReactElement {
  const { t } = useTranslation();

  return (
    <>
      <UiTypography variant="medium15" sx={styles.copyright}>
        {t('footer.copyright')}
      </UiTypography>
      <Stack direction="row" gap="0.875rem" alignItems="center">
        <Box sx={styles.gmailWrapper}>
          <UiTypography variant="medium15" sx={styles.gmailText}>
            info@vilnacrm.com
          </UiTypography>
        </Box>
        <SocialMediaList socialLinks={socialLinks} />
      </Stack>
    </>
  );
}

export default CopyrightNoticeAndLinks;
