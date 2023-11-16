import { Box, Container } from '@mui/material';
import { useEffect, useRef } from 'react';

import UnlimitedIntegrationsCardItems from '@/features/landing/components/UnlimitedIntegrationsSection/UnlimitedIntegrationsCardItems/UnlimitedIntegrationsCardItems';
import UnlimitedIntegrationsSlider
  from '@/features/landing/components/UnlimitedIntegrationsSection/UnlimitedIntegrationsSlider/UnlimitedIntegrationsSlider';
import { useScreenSize } from '@/features/landing/hooks/useScreenSize/useScreenSize';
import { UNLIMITED_INTEGRATIONS_CARD_ITEMS } from '@/features/landing/utils/constants/constants';

import {
  UnlimitedIntegrationsTexts,
} from '../UnlimitedIntegrationsTexts/UnlimitedIntegrationsTexts';

export default function UnlimitedIntegrationsSection() {
  const { isTablet, isSmallest, isMobile } = useScreenSize();

  const paddingLeftForMainBox = useRef('0px');
  const paddingRightForMainBox = useRef('0px');

  useEffect(() => {
    if (isMobile || isSmallest) {
      paddingLeftForMainBox.current = '23px';
      paddingRightForMainBox.current = '23px';
      return;
    }
    if (isTablet) {
      paddingLeftForMainBox.current = '34px';
      paddingRightForMainBox.current = '34px';
      return;
    }

    paddingLeftForMainBox.current = '0px';
    paddingRightForMainBox.current = '0px';
  }, [isTablet, isSmallest, isMobile]);

  return (
    <Box
      sx={{
        padding: '56px 0 56px 0',
        paddingLeft: paddingLeftForMainBox.current,
        paddingRight: paddingRightForMainBox.current,
        background: '#FFF',
      }}
    >
      <Container sx={{ width: '100%', maxWidth: '1192px', margin: '0 auto', padding: '0' }}>
        <UnlimitedIntegrationsTexts />
        {isMobile || isSmallest ? (
          <UnlimitedIntegrationsSlider cardItems={UNLIMITED_INTEGRATIONS_CARD_ITEMS} />
        ) : (
          <UnlimitedIntegrationsCardItems cardItems={UNLIMITED_INTEGRATIONS_CARD_ITEMS} />
        )}
      </Container>
    </Box>
  );
}
