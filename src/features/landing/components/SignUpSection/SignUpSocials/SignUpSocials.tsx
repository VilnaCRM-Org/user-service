import { Box, Grid, Typography } from '@mui/material';
import { useTranslation } from 'react-i18next';

import SocialLink from '@/components/ui/SocialLink/SocialLink';
import { useScreenSize } from '@/features/landing/hooks/useScreenSize/useScreenSize';
import { ISocialLink } from '@/features/landing/types/social/types';

const styles = {
  typographyHeading: {
    color: '#57595B',
    fontFamily: 'GolosText-Bold, sans-serif',
    fontSize: '22px',
    fontStyle: 'normal',
    fontWeight: '700',
    lineHeight: 'normal',
    marginTop: '40px',
  },
  typographyHeadingMobileOrSmaller: {
    marginTop: '0',
  },
};

export default function SignUpSocials({ socialLinks }: { socialLinks: ISocialLink[] }) {
  const { t } = useTranslation();
  const { isSmallest, isMobile, isSmallTablet } = useScreenSize();

  return (
    <Box sx={{ width: '100%', maxWidth: '790px' }}>
      <Typography
        component="p"
        variant="body1"
        style={{
          ...styles.typographyHeading,
          ...(isSmallest || isMobile ? styles.typographyHeadingMobileOrSmaller : {}),
        }}
      >
        {t('Log in with a convenient social network:')}
      </Typography>

      <Grid
        container
        spacing={(isSmallest || isMobile) ? 1 : 1.5}
        sx={{
          width: '100%',
          marginTop: '24px',
          display: 'flex',
          justifyContent: isSmallest || isMobile || isSmallTablet ? 'center' : 'flex-start',
        }}
      >
        {socialLinks.map(({ id, linkHref, icon, title }) => (
          <Grid item key={id}>
            <SocialLink linkHref={linkHref} icon={icon} title={title} />
          </Grid>
        ))}
      </Grid>
    </Box>
  );
}
