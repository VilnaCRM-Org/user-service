import { Grid, Typography } from '@mui/material';
import { useTranslation } from 'react-i18next';

import CustomLink from '@/components/ui/CustomLink/CustomLink';
import { ISocialLink } from '@/features/landing/types/social/types';

import { SignUpSocials } from '../SignUpSocials/SignUpSocials';

export default function SignUpTextsContent({ socialLinks }: {
  socialLinks: ISocialLink[]
}) {
  const { t } = useTranslation();

  return (
    <Grid item>
      <Typography>
        {t('Register now and free up time for business development with ')}
        <CustomLink href='/'>VilnaCRM</CustomLink>
      </Typography>
      <SignUpSocials socialLinks={socialLinks} />
    </Grid>
  );
}
