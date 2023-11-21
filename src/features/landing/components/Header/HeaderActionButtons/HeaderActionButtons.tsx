import { Grid } from '@mui/material';
import * as React from 'react';
import { useTranslation } from 'react-i18next';

import { Button } from '@/components/ui/Button/Button';
import { useScreenSize } from '@/features/landing/hooks/useScreenSize/useScreenSize';
import { TRANSLATION_NAMESPACE } from '@/features/landing/utils/constants/constants';

interface IHeaderActionButtonsProps {
  onSignInButtonClick: () => void;
  onTryItOutButtonClick: () => void;
}

export function HeaderActionButtons({
  onSignInButtonClick,
  onTryItOutButtonClick,
}: IHeaderActionButtonsProps) {
  const { t } = useTranslation(TRANSLATION_NAMESPACE);
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
          style={{ width: '100%' }}
        >
          {t('header.actions.log_in')}
        </Button>
      </Grid>
      <Grid item>
        <Button
          customVariant="light-blue"
          onClick={onTryItOutButtonClick}
          style={{ width: '100%' }}
        >
          {t('header.actions.try_it_out')}
        </Button>
      </Grid>
    </Grid>
  );
}
