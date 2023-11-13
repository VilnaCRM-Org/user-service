import { useState } from 'react';
import { Container } from '@mui/material';
import {
  WhyWeSectionHeader,
} from '@/features/landing/components/WhyWeSection/WhyWeSectionHeader/WhyWeSectionHeader';
import { WhyWeSectionSlider } from '../WhyWeSectionSlider/WhyWeSectionSlider';
import {
  WhyWeSectionCardItems,
} from '@/features/landing/components/WhyWeSection/WhyWeSectionCardItems/WhyWeSectionCardItems';
import { useScreenSize } from '@/features/landing/hooks/useScreenSize/useScreenSize';
import { CARD_ITEMS } from '@/features/landing/utils/constants/constants';
import { IWhyWeCardItem } from '@/features/landing/types/why-we/types';

export function WhyWeSection() {
  const [
    cardItems,
    setCardItems,
  ] = useState<IWhyWeCardItem[]>(CARD_ITEMS);
  const { isMobile, isSmallest, isTablet } = useScreenSize();

  return (
    <Container sx={{ padding: '56px 0 89px 0' }}>
      <WhyWeSectionHeader
        style={{
          padding:
            (isTablet) ? '0 32px 0 32px'
              : (isMobile || isSmallest) ? '0 15px 0 15px'
                : '0 0 0 0',
        }} />
      <>
        {(isMobile || isSmallest) ? <WhyWeSectionSlider cardItems={cardItems} /> :
          <WhyWeSectionCardItems cardItems={cardItems} />}
      </>
    </Container>
  );
}
