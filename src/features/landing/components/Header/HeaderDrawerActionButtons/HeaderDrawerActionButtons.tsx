import { Grid } from '@mui/material';
import * as React from 'react';
import { useTranslation } from 'react-i18next';

import Button from '@/components/ui/Button/Button';

import { TRANSLATION_NAMESPACE } from '../../../utils/constants/constants';

interface IHeaderDrawerActionButtonsProps {
  onSignInButtonClick: () => void;
  onTryItOutButtonClick: () => void;
  onDrawerClose: () => void;
}

export default function HeaderDrawerActionButtons({
  onSignInButtonClick,
  onTryItOutButtonClick,
  onDrawerClose,
}: IHeaderDrawerActionButtonsProps) {
  const { t } = useTranslation(TRANSLATION_NAMESPACE);

  const handleTryItOutClickWithDrawerClose = () => {
    onDrawerClose();
    onTryItOutButtonClick();
  };

  return (
    <Grid
      container
      justifyContent="space-between"
      flexGrow={0}
      sx={{
        display: 'flex',
        width: '100%',
        maxWidth: '100%',
        md: { display: 'none' },
        marginBottom: '16px',
      }}
    >
      <Grid item xs={6} sm={6}>
        <Button
          customVariant="transparent-white"
          onClick={onSignInButtonClick}
          fullWidth
          style={{ marginRight: '4.5px' }}
        >
          {t('header.actions.log_in')}
        </Button>
      </Grid>
      <Grid item xs={6} sm={6}>
        <Button
          customVariant="light-blue"
          onClick={handleTryItOutClickWithDrawerClose}
          fullWidth
          style={{ marginLeft: '4.5px' }}
        >
          {t('header.actions.try_it_out')}
        </Button>
      </Grid>
    </Grid>
  );
}
