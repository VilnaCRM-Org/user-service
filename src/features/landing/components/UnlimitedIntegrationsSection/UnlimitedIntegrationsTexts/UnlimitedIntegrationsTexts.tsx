import { Box, Typography } from '@mui/material';
import { useTranslation } from 'react-i18next';

import { useScreenSize } from '@/features/landing/hooks/useScreenSize/useScreenSize';
import { TRANSLATION_NAMESPACE } from '@/features/landing/utils/constants/constants';

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
    textAlign: 'inherit',
  },
};

export function UnlimitedIntegrationsTexts() {
  const { t } = useTranslation(TRANSLATION_NAMESPACE);
  const { isMobile, isSmallest } = useScreenSize();

  return (
    <Box
      sx={{
        ...style.mainBox,
        alignItems: isMobile || isSmallest ? 'flex-start' : 'center',
        textAlign: isMobile || isSmallest ? 'left' : 'center',
      }}
    >
      <Typography
        component="h2"
        variant="h2"
        style={{
          ...style.mainHeading,
          fontSize: isMobile || isSmallest ? '22px' : '36px',
        }}
      >
        {t('unlimited_possibilities.main_heading_text')}
      </Typography>

      <Typography
        component="h3"
        variant="h3"
        style={{
          ...style.secondaryHeading,
          fontSize: isMobile || isSmallest ? '28px' : '46px',
          textAlign: 'inherit',
        }}
      >
        {t('unlimited_possibilities.secondary_heading_text')}
      </Typography>
    </Box>
  );
}
