import { useState } from 'react';
import { Container } from '@mui/material';
import { WhyWeSectionHeader } from '@/features/landing/components/WhyWeSection/WhyWeSectionHeader/WhyWeSectionHeader';
import { WhyWeSectionSlider } from '../WhyWeSectionSlider/WhyWeSectionSlider';
import { WhyWeSectionCardItems } from '@/features/landing/components/WhyWeSection/WhyWeSectionCardItems/WhyWeSectionCardItems';
import { useScreenSize } from '@/features/landing/hooks/useScreenSize/useScreenSize';
import { WHY_WE_CARD_ITEMS } from '@/features/landing/utils/constants/constants';
import { IWhyWeCardItem } from '@/features/landing/types/why-we/types';

const TOOLTIP_ICONS: string[] = [
  '/assets/img/TooltipIcons/1.png',
  '/assets/img/TooltipIcons/2.png',
  '/assets/img/TooltipIcons/3.png',
  '/assets/img/TooltipIcons/4.png',
  '/assets/img/TooltipIcons/5.png',
  '/assets/img/TooltipIcons/6.png',
  '/assets/img/TooltipIcons/7.png',
  '/assets/img/TooltipIcons/8.png',
];

export function WhyWeSection() {
  const [cardItems, setCardItems] = useState<IWhyWeCardItem[]>(WHY_WE_CARD_ITEMS);
  const { isMobile, isSmallest, isTablet } = useScreenSize();

  return (
    <Container sx={{ padding: '56px 0 56px 0' }}>
      <WhyWeSectionHeader
        style={{
          padding: isTablet
            ? '0 32px 0 32px'
            : isMobile || isSmallest
            ? '0 15px 0 15px'
            : '0 0 0 0',
        }}
      />
      <>
        {isMobile || isSmallest ? (
          <WhyWeSectionSlider cardItems={cardItems} />
        ) : (
          <WhyWeSectionCardItems cardItems={cardItems} tooltipIcons={TOOLTIP_ICONS} />
        )}
      </>
    </Container>
  );
}
