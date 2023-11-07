import { useTranslation } from 'react-i18next';
import { useScreenSize } from '@/features/landing/hooks/useScreenSize/useScreenSize';
import { Grid } from '@mui/material';
import { Button } from '@/components/ui/Button/Button';
import * as React from 'react';

interface IHeaderDrawerActionButtonsProps {
  onSignInButtonClick: () => void;
  onTryItOutButtonClick: () => void;
}

export function HeaderDrawerActionButtons({
                                      onSignInButtonClick,
                                      onTryItOutButtonClick,
                                    }: IHeaderDrawerActionButtonsProps) {
  const { t } = useTranslation();

  return (
    <Grid container gap={'8px'} justifyContent={'flex-end'} flexGrow={0}
          sx={{ maxWidth: '238px', md: { display: 'none' }, marginBottom: '16px'}}>
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
