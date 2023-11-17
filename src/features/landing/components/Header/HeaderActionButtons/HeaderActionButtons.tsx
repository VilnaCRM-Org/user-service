import * as React from 'react';
import { Grid } from '@mui/material';

import { Button } from '@/components/ui/Button/Button';
import { useTranslation } from 'react-i18next';
import { useScreenSize } from '@/features/landing/hooks/useScreenSize/useScreenSize';

interface IHeaderActionButtonsProps {
  onSignInButtonClick: () => void;
  onTryItOutButtonClick: () => void;
}

export function HeaderActionButtons({
  onSignInButtonClick,
  onTryItOutButtonClick,
}: IHeaderActionButtonsProps) {
  const { t } = useTranslation();
  const { isMobile, isSmallest, isSmallTablet } = useScreenSize();

  if (isMobile || isSmallest || isSmallTablet) {
    return null;
  }

  return (
    <Grid
      container
      sx={{
        display: 'flex',
        maxWidth: '250px',
        justifyContent: 'flex-end',
        alignItems: 'center',
        gap: '8px',
        width: '100%',
        md: { display: 'none' },
      }}
    >
      <Grid item>
        <Button
          customVariant="transparent-white"
          onClick={onSignInButtonClick}
          style={{ width: '100%', maxWidth: '119px' }}
        >
          {t('Log in')}
        </Button>
      </Grid>
      <Grid item>
        <Button
          customVariant="light-blue"
          onClick={onTryItOutButtonClick}
          style={{ width: '100%', maxWidth: '119px' }}
        >
          {t('Try it out')}
        </Button>
      </Grid>
    </Grid>
  );
}
