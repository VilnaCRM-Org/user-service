import { Box, Link } from '@mui/material';
import React from 'react';
import { useTranslation } from 'react-i18next';

import { UiButton } from '@/components';
import UiCardList from '@/components/UiCardList';

import { cardList } from './constants';
import { Heading } from './Heading';
import styles from './styles';

function WhyUs(): React.ReactElement {
  const { t } = useTranslation();

  return (
    <Box sx={styles.wrapper} id="Advantages" component="section">
      <Heading />
      <UiCardList cardList={cardList} />
      <Link href="#signUp" aria-label={t('why_us.aria_label')}>
        <UiButton variant="contained" size="small" sx={styles.button}>
          {t('why_us.button_text')}
        </UiButton>
      </Link>
    </Box>
  );
}

export default WhyUs;
