import { Box, Container } from '@mui/material';
import {
  UnlimitedIntegrationsTexts,
} from '../UnlimitedIntegrationsTexts/UnlimitedIntegrationsTexts';
import { useScreenSize } from '@/features/landing/hooks/useScreenSize/useScreenSize';
import {
  UnlimitedIntegrationsCardItems,
} from '@/features/landing/components/UnlimitedIntegrationsSection/UnlimitedIntegrationsCardItems/UnlimitedIntegrationsCardItems';
import { UNLIMITED_INTEGRATIONS_CARD_ITEMS } from '@/features/landing/utils/constants/constants';
import {
  UnlimitedIntregrationsSlider,
} from '@/features/landing/components/UnlimitedIntegrationsSection/UnlimitedIntegrationsSlider/UnlimitedIntegrationsSlider';

export function UnlimitedIntegrationsSection() {
  const { isTablet, isSmallest, isMobile } = useScreenSize();

  return (
    <Box
      sx={{
        padding: '56px 0 56px 0',
        paddingLeft: (isMobile || isSmallest) ? '0' : (isTablet) ? '34px' : '0',
        paddingRight: (isMobile || isSmallest) ? '0' : (isTablet) ? '34px' : '0',
        background: '#FFF',
      }}>
      <Container sx={{ width: '100%', maxWidth: '1192px', margin: '0 auto' }}>
        <UnlimitedIntegrationsTexts />
        {
          (isMobile || isSmallest) ?
            <UnlimitedIntregrationsSlider cardItems={UNLIMITED_INTEGRATIONS_CARD_ITEMS} /> :
            <UnlimitedIntegrationsCardItems cardItems={UNLIMITED_INTEGRATIONS_CARD_ITEMS} />
        }
      </Container>
    </Box>
  );
}
