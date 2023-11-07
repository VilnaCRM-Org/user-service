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
  const { isMobile, isSmallest } = useScreenSize();

  if (isMobile || isSmallest) {
    return null;
  }

  return (
    <Grid container gap={'8px'} justifyContent={'flex-end'} flexGrow={0}
          sx={{ maxWidth: '238px', md: { display: 'none' } }}>
      <Grid item>
        <Button customVariant={'transparent-white'} onClick={onSignInButtonClick}>
          {t('Увійти')}
        </Button>
      </Grid>
      <Grid item>
        <Button customVariant={'light-blue'} onClick={onTryItOutButtonClick}>
          {t('Спробувати')}
        </Button>
      </Grid>
    </Grid>
  );
}
