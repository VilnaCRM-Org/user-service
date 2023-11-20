import { Grid, Typography } from '@mui/material';
import { useTranslation } from 'react-i18next';

import CustomLink from '@/components/ui/CustomLink/CustomLink';
import { ISocialLink } from '@/features/landing/types/social/types';

import SignUpSocials from '../SignUpSocials/SignUpSocials';

const styles = {
  mainHeading: {
    color: '#1A1C1E',
    fontFamily: 'GolosText-Bold, sans-serif',
    fontSize: '46px',
    fontStyle: 'normal',
    fontWeight: '700',
    lineHeight: 'normal',
    marginBottom: '40px',
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

export default function SignUpTextsContent({ socialLinks }: {
  socialLinks: ISocialLink[]
}) {
  const { t } = useTranslation();

  return (
    <Grid item lg={6} md={12}>
      <Typography component='h2' variant='h2' style={{
        ...styles.mainHeading,
      }}>
        {t('Register now and free up time for business development with ')}
        <CustomLink href='/' style={{ ...styles.mainLink }}>VilnaCRM</CustomLink>
      </Typography>
      <SignUpSocials socialLinks={socialLinks} />
    </Grid>
  );
}
