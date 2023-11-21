import { Grid, Typography } from '@mui/material';
import { useTranslation } from 'react-i18next';

import CustomLink from '@/components/ui/CustomLink/CustomLink';
import { useScreenSize } from '@/features/landing/hooks/useScreenSize/useScreenSize';
import { ISocialLink } from '@/features/landing/types/social/types';
import { TRANSLATION_NAMESPACE } from '@/features/landing/utils/constants/constants';

import SignUpSocials from '../SignUpSocials/SignUpSocials';

const styles = {
  mainGrid: {
    display: 'flex',
    flexDirection: 'column',
    alignItems: 'center',
    textAlign: 'left',
  },
  mainHeading: {
    color: '#1A1C1E',
    fontFamily: 'GolosText-Bold, sans-serif',
    fontSize: '46px',
    fontStyle: 'normal',
    fontWeight: '700',
    lineHeight: 'normal',
    marginBottom: '40px',
  },
  mainHeadingMobileOrSmaller: {
    fontSize: '28px',
    marginBottom: '20px',
  },
  mainLink: {
    color: '#1EAEFF',
    fontFamily: 'GolosText-Bold, sans-serif',
    fontSize: '46px',
    fontStyle: 'normal',
    fontWeight: '700',
    lineHeight: 'normal',
  },
};

export default function SignUpTextsContent({ socialLinks }: { socialLinks: ISocialLink[] }) {
  const { t } = useTranslation(TRANSLATION_NAMESPACE);
  const { isSmallest, isMobile, isTablet } = useScreenSize();

  return (
    <Grid
      item
      lg={6}
      md={12}
      sx={{
        ...styles.mainGrid,
        textAlign: isSmallest || isMobile || isTablet ? 'center' : styles.mainGrid.textAlign,
      }}
    >
      <Typography
        component="h2"
        variant="h2"
        style={{
          ...styles.mainHeading,
          ...(isSmallest || isMobile ? styles.mainHeadingMobileOrSmaller : {}),
        }}
      >
        {t('sign_up.main_heading')}
        <CustomLink href="/" style={{ ...styles.mainLink }}>
          VilnaCRM
        </CustomLink>
      </Typography>
      <SignUpSocials socialLinks={socialLinks} />
    </Grid>
  );
}
