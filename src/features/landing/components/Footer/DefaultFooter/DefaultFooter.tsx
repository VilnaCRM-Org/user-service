import { Box, Link, Stack } from '@mui/material';
import Image from 'next/image';
import { useMemo } from 'react';
import { useTranslation } from 'react-i18next';

import { UiTypography } from '@/components/';

import Logo from '../../../assets/svg/logo/Logo.svg';
import { SocialMedia } from '../../../types/social-media';
import SocialMediaList from '../../SocialMedia/SocialMediaList/SocialMediaList';
import { PrivacyPolicy } from '../PrivacyPolicy';
import { VilnaCRMGmail } from '../VilnaCRMGmail';

import styles from './styles';

function DefaultFooter({
  socialLinks,
}: {
  socialLinks: SocialMedia[];
}): React.ReactElement {
  const { t } = useTranslation();

  const currentDate: Date = useMemo(() => new Date(), []);
  const currentYear: number = useMemo(
    () => currentDate.getFullYear(),
    [currentDate]
  );

  return (
    <Stack sx={styles.footerWrapper}>
      <Stack height="4.188rem" alignItems="center" flexDirection="row">
        <Box sx={styles.topWrapper}>
          <Stack
            direction="row"
            justifyContent="space-between"
            alignItems="center"
          >
            <Link href="/#">
              <Image
                src={Logo}
                alt={t('footer.logo_alt')}
                width={143}
                height={48}
              />
            </Link>
            <PrivacyPolicy />
          </Stack>
        </Box>
      </Stack>
      <Stack sx={styles.copyrightAndLinksWrapper}>
        <Box sx={styles.bottomWrapper}>
          <Stack sx={styles.copyrightAndLinks}>
            <UiTypography variant="medium15" sx={styles.copyright}>
              {t('footer.copyright')} {currentYear}
            </UiTypography>
            <Stack direction="row" gap="0.875rem" alignItems="center">
              <VilnaCRMGmail />
              <SocialMediaList socialLinks={socialLinks} />
            </Stack>
          </Stack>
        </Box>
      </Stack>
    </Stack>
  );
}

export default DefaultFooter;
