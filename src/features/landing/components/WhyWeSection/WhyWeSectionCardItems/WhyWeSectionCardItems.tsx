import { Container } from '@mui/material';

import { useScreenSize } from '@/features/landing/hooks/useScreenSize/useScreenSize';
import { IWhyWeCardItem } from '@/features/landing/types/why-we/types';

import WhyWeSectionCardItemsRow from '../WhyWeSectionCardItemsRow/WhyWeSectionCardItemsRow';

interface IWhyWeSectionCardItemsProps {
  cardItems: IWhyWeCardItem[];
  tooltipIcons: string[];
}

export default function WhyWeSectionCardItems({
  cardItems,
  tooltipIcons,
}: IWhyWeSectionCardItemsProps) {
  const { isLaptop, isTablet, isMobile, isSmallest } = useScreenSize();

  return (
    <Container sx={{ width: '100%', maxWidth: '1192px' }}>
      <WhyWeSectionCardItemsRow
        cardItems={cardItems.slice(0, 3)}
        tooltipIcons={tooltipIcons}
        style={{
          padding: isLaptop || isTablet || isMobile || isSmallest ? '0 31px 0 32px' : '0',
        }}
      />
      <WhyWeSectionCardItemsRow
        cardItems={cardItems.slice(3, 6)}
        style={{
          marginTop: '13px',
          padding: isLaptop || isTablet || isMobile || isSmallest ? '0 31px 0 32px' : '0',
        }}
        tooltipIcons={tooltipIcons}
      />
    </Container>
  );
}
