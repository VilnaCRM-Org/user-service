import { Container } from '@mui/material';
import { useState } from 'react';

import useScreenSize from '../../../hooks/useScreenSize/useScreenSize';
import IWhyWeCardItem from '../../../types/why-we/types';
import { WHY_WE_CARD_ITEMS } from '../../../utils/constants/constants';
import WhyWeSectionCardItems from '../WhyWeSectionCardItems/WhyWeSectionCardItems';
import WhyWeSectionHeader from '../WhyWeSectionHeader/WhyWeSectionHeader';
import WhyWeSectionSlider from '../WhyWeSectionSlider/WhyWeSectionSlider';

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

export default function WhyWeSection() {
  const [cardItems] = useState<IWhyWeCardItem[]>(WHY_WE_CARD_ITEMS);
  const { isMobile, isSmallest, isTablet } = useScreenSize();

  return (
    <Container sx={{ padding: '56px 31px 56px 32px', marginBottom: '89px' }}>
      <WhyWeSectionHeader />
      {isMobile || isSmallest || isTablet ? (
        <WhyWeSectionSlider cardItems={cardItems} />
      ) : (
        <WhyWeSectionCardItems cardItems={cardItems} tooltipIcons={TOOLTIP_ICONS} />
      )}
    </Container>
  );
}
