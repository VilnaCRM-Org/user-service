import { Container } from '@mui/material';
import { useState } from 'react';

import TooltipIcon1 from '@/assets/img/TooltipIcons/1.png';
import TooltipIcon2 from '@/assets/img/TooltipIcons/2.png';
import TooltipIcon3 from '@/assets/img/TooltipIcons/3.png';
import TooltipIcon4 from '@/assets/img/TooltipIcons/4.png';
import TooltipIcon5 from '@/assets/img/TooltipIcons/5.png';
import TooltipIcon6 from '@/assets/img/TooltipIcons/6.png';
import TooltipIcon7 from '@/assets/img/TooltipIcons/7.png';
import TooltipIcon8 from '@/assets/img/TooltipIcons/8.png';

import useScreenSize from '../../../hooks/useScreenSize/useScreenSize';
import IWhyWeCardItem from '../../../types/why-we/types';
import { WHY_WE_CARD_ITEMS } from '../../../utils/constants/constants';
import WhyWeSectionCardItems from '../WhyWeSectionCardItems/WhyWeSectionCardItems';
import WhyWeSectionHeader from '../WhyWeSectionHeader/WhyWeSectionHeader';
import WhyWeSectionSlider from '../WhyWeSectionSlider/WhyWeSectionSlider';

const TOOLTIP_ICONS: string[] = [
  TooltipIcon1,
  TooltipIcon2,
  TooltipIcon3,
  TooltipIcon4,
  TooltipIcon5,
  TooltipIcon6,
  TooltipIcon7,
  TooltipIcon8,
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
