import { Grid } from '@mui/material';
import * as React from 'react';
import { useTranslation } from 'react-i18next';

import Button from '@/components/ui/Button/Button';

import useScreenSize from '../../../hooks/useScreenSize/useScreenSize';

interface IHeaderActionButtonsProps {
  onSignInButtonClick: () => void;
  onTryItOutButtonClick: () => void;
}

export default function HeaderActionButtons({
  onSignInButtonClick,
  onTryItOutButtonClick,
}: IHeaderActionButtonsProps) {
  const { t } = useTranslation();
  const { isMobile, isSmallest, isSmallTablet, isBigTablet } = useScreenSize();

  if (isMobile || isSmallest || isSmallTablet || isBigTablet) {
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
        width: '100%',
        gap: '8px',
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
