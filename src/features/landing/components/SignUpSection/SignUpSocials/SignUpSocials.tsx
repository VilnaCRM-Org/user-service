import { Box, Grid, Typography } from '@mui/material';
import { useTranslation } from 'react-i18next';

import SocialLink from '@/components/ui/SocialLink/SocialLink';
import { ISocialLink } from '@/features/landing/types/social/types';

export default function SignUpSocials({ socialLinks }: {
  socialLinks: ISocialLink[]
}) {
  const { t } = useTranslation();

  return (
    <Box>
      <Typography component='p' variant='body1' style={{
        color: '#57595B',
        fontFamily: 'GolosText-Bold, sans-serif',
        fontSize: '22px',
        fontStyle: 'normal',
        fontWeight: '700',
        lineHeight: 'normal',
        marginTop: '40px',
      }}>
        {t('Log in with a convenient social network:')}
      </Typography>

      <Grid container spacing={1.5} sx={{
        width: '100%',
        marginTop: '24px',
      }}>
        {
          socialLinks.map(({ id, linkHref, icon, title }) =>
            <Grid item key={id}>
              <SocialLink
                linkHref={linkHref}
                icon={icon}
                title={title} />
            </Grid>)
        }
      </Grid>
    </Box>
  );
}
