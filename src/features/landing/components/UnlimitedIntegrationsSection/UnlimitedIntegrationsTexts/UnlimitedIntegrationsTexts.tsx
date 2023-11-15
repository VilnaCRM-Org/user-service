import { useTranslation } from 'react-i18next';
import { Box, Typography } from '@mui/material';

const style = {
  mainBox: {
    display: 'flex',
    alignItems: 'center',
    flexDirection: 'column',
    marginBottom: '32px',
  },
  mainHeading: {
    borderRadius: '16px',
    background: '#FFC01E',
    display: 'flex',
    alignItems: 'center',
    color: '#1A1C1E',
    fontFamily: 'GolosText-Regular, sans-serif',
    fontSize: '36px',
    fontStyle: 'normal',
    fontWeight: '600',
    lineHeight: 'normal',
    padding: '12px 32px',
    marginBottom: '7px',
  },
  secondaryHeading: {
    color: '#1A1C1E',
    fontFamily: 'GolosText-Regular, sans-serif',
    fontSize: '46px',
    fontStyle: 'normal',
    fontWeight: '700',
    lineHeight: 'normal',
    textAlign: 'center'
  },
};

export function UnlimitedIntegrationsTexts() {
  const { t } = useTranslation();

  return (
    <Box sx={{ ...style.mainBox }}>
      <Typography component={'h2'} variant={'h2'} sx={{
        ...style.mainHeading,
      }}>
        {t('Unlimited')}
      </Typography>

      <Typography component={'h3'} variant={'h3'} sx={{ ...style.secondaryHeading }}>
        {t('possibilities of integration')}
      </Typography>
    </Box>
  );
}
